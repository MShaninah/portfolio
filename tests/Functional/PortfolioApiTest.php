<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Profile;
use App\Entity\Project;
use App\Entity\Skill;
use App\Tests\ResetsRateLimiters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * End-to-end coverage of the portfolio endpoints against a real schema.
 */
final class PortfolioApiTest extends WebTestCase
{
    use ResetsRateLimiters;

    private const string PASSWORD = 'test-password';
    private const string IP = '192.0.2.50';

    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient([], ['REMOTE_ADDR' => self::IP]);
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        // Each test logs in, and the login throttle (5 per 15 minutes per IP)
        // outlives a single request, so without this reset the sixth test in
        // the class would be rate limited instead of authenticated.
        $this->resetRateLimiters(self::IP);

        $tool = new SchemaTool($this->em);
        $classes = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($classes);
        $tool->createSchema($classes);

        $this->em->persist(new Profile()->setEmail('hi@example.test')->setPhone('+49 123'));
        $this->em->persist(new Skill()->setName('Symfony'));
        $this->em->persist(new Skill()->setName('Doctrine'));
        $this->em->persist(
            new Project()
                ->setSlug('alpha')
                ->setTitle('Alpha')
                ->setYear('2026')
                ->setCategory('Backend')
                ->setHighlights(['PHP'])
                ->setLinks([['label' => 'Repo', 'href' => 'https://example.test/repo']]),
        );
        $this->em->flush();
    }

    private function token(): string
    {
        $this->client->request(
            'POST',
            '/api/admin/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['password' => self::PASSWORD], \JSON_THROW_ON_ERROR),
        );

        return json_decode((string) $this->client->getResponse()->getContent(), true)['token'];
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer '.$this->token(), 'CONTENT_TYPE' => 'application/json'];
    }

    private function decode(): mixed
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }

    public function testThePublicPortfolioIsServed(): void
    {
        $this->client->request('GET', '/api/portfolio');

        self::assertResponseIsSuccessful();
        $body = $this->decode();

        self::assertSame('hi@example.test', $body['profile']['email']);
        self::assertSame(
            [
                ['name' => 'Doctrine', 'category' => 'backend', 'level' => 'advanced'],
                ['name' => 'Symfony', 'category' => 'backend', 'level' => 'advanced'],
            ],
            $body['skills'],
        );
        self::assertCount(1, $body['projects']);
        self::assertSame('alpha', $body['projects'][0]['slug']);
    }

    public function testPublicProjectsAreListedAndFetchable(): void
    {
        $this->client->request('GET', '/api/projects');
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->decode());

        $this->client->request('GET', '/api/projects/alpha');
        self::assertResponseIsSuccessful();
        self::assertSame('Alpha', $this->decode()['title']);

        $this->client->request('GET', '/api/projects/does-not-exist');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAValidTokenGrantsAccessToTheAdminPortfolio(): void
    {
        // The complete replacement auth flow: password -> signed token -> use.
        $this->client->request('GET', '/api/admin/portfolio', server: $this->authHeaders());

        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('projects', $this->decode());
    }

    public function testAProjectCanBeCreated(): void
    {
        $this->client->request('POST', '/api/admin/projects', server: $this->authHeaders(), content: json_encode([
            'id' => 'beta',
            'title' => 'Beta',
            'year' => '2026',
            'category' => 'AI',
            'stack' => ['PHP', 'Symfony'],
            'links' => [['label' => 'Demo', 'href' => 'https://example.test/demo']],
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $created = $this->em->getRepository(Project::class)->findOneBy(['slug' => 'beta']);
        self::assertInstanceOf(Project::class, $created);
        // 'stack' from the payload is persisted as the entity's 'highlights'.
        self::assertSame(['PHP', 'Symfony'], $created->getHighlights());
        // Previously 'links' could never be written: no DTO carried the field.
        self::assertSame([['label' => 'Demo', 'href' => 'https://example.test/demo']], $created->getLinks());
    }

    public function testDuplicateSlugIsAConflict(): void
    {
        $this->client->request('POST', '/api/admin/projects', server: $this->authHeaders(), content: json_encode([
            'id' => 'alpha',
            'title' => 'Alpha again',
            'year' => '2026',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function dangerousHrefs(): iterable
    {
        yield 'javascript' => ['javascript:alert(document.domain)'];
        yield 'data uri' => ['data:text/html;base64,PHNjcmlwdD4='];
        yield 'vbscript' => ['vbscript:msgbox(1)'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dangerousHrefs')]
    public function testDangerousLinkSchemesAreRejected(string $href): void
    {
        // The Vue client navigates to project link hrefs, so a stored
        // "javascript:" URL would be stored XSS.
        $this->client->request('POST', '/api/admin/projects', server: $this->authHeaders(), content: json_encode([
            'id' => 'evil',
            'title' => 'Evil',
            'year' => '2026',
            'links' => [['label' => 'Click', 'href' => $href]],
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertNull($this->em->getRepository(Project::class)->findOneBy(['slug' => 'evil']));
    }

    public function testAnUnknownCategoryIsRejected(): void
    {
        $this->client->request('POST', '/api/admin/projects', server: $this->authHeaders(), content: json_encode([
            'id' => 'gamma',
            'title' => 'Gamma',
            'year' => '2026',
            'category' => 'NotACategory',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testAProjectCanBeUpdatedPartially(): void
    {
        $this->client->request('PUT', '/api/admin/projects/alpha', server: $this->authHeaders(), content: json_encode([
            'tagline' => 'Only the tagline changes',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();

        $this->em->clear();
        $project = $this->em->getRepository(Project::class)->findOneBy(['slug' => 'alpha']);
        self::assertInstanceOf(Project::class, $project);
        self::assertSame('Only the tagline changes', $project->getTagline());
        self::assertSame('Alpha', $project->getTitle(), 'omitted fields must stay untouched');
    }

    public function testTheRepositoryLinkCanBeAddedAndRemoved(): void
    {
        // The admin exposes a single "Repository URL" field which it maps onto
        // the API's links array, so that round trip has to hold.
        $this->client->request('PUT', '/api/admin/projects/alpha', server: $this->authHeaders(), content: json_encode([
            'links' => [['label' => 'Repo', 'href' => 'https://github.com/example/alpha']],
        ], \JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();

        $this->em->clear();
        $project = $this->em->getRepository(Project::class)->findOneBy(['slug' => 'alpha']);
        self::assertInstanceOf(Project::class, $project);
        self::assertSame(
            [['label' => 'Repo', 'href' => 'https://github.com/example/alpha']],
            $project->getLinks(),
        );

        // Clearing the field sends an empty array, which must remove the link.
        $this->client->request('PUT', '/api/admin/projects/alpha', server: $this->authHeaders(), content: json_encode([
            'links' => [],
        ], \JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();

        $this->em->clear();
        $project = $this->em->getRepository(Project::class)->findOneBy(['slug' => 'alpha']);
        self::assertInstanceOf(Project::class, $project);
        self::assertSame([], $project->getLinks());
    }

    public function testHighlightsAcceptRealBulletPoints(): void
    {
        // Regression guard: highlights are sentences, not tech tags. A 40-char
        // cap made every existing project uneditable through the admin.
        $bullet = 'Release blocked unless the target PHP carries the php2shell RCE fixes (CVE-2026-109618)';
        self::assertGreaterThan(40, \strlen($bullet));

        $this->client->request('PUT', '/api/admin/projects/alpha', server: $this->authHeaders(), content: json_encode([
            'stack' => [$bullet],
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();

        $this->em->clear();
        $project = $this->em->getRepository(Project::class)->findOneBy(['slug' => 'alpha']);
        self::assertInstanceOf(Project::class, $project);
        self::assertSame([$bullet], $project->getHighlights());
    }

    public function testAbsurdlyLongHighlightsAreStillRejected(): void
    {
        $this->client->request('PUT', '/api/admin/projects/alpha', server: $this->authHeaders(), content: json_encode([
            'stack' => [str_repeat('x', 201)],
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testAProjectCanBeDeleted(): void
    {
        $this->client->request('DELETE', '/api/admin/projects/alpha', server: $this->authHeaders());

        self::assertResponseIsSuccessful();
        $this->em->clear();
        self::assertNull($this->em->getRepository(Project::class)->findOneBy(['slug' => 'alpha']));
    }

    public function testSkillsAreReplacedAsASet(): void
    {
        $this->client->request('PUT', '/api/admin/skills', server: $this->authHeaders(), content: json_encode([
            'skills' => [
                ['name' => 'Symfony', 'category' => 'backend', 'level' => 'advanced'],
                ['name' => 'PHP', 'category' => 'backend', 'level' => 'intermediate'],
                ['name' => 'Symfony', 'category' => 'backend', 'level' => 'advanced'],
            ],
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        self::assertSame(
            [
                ['name' => 'Symfony', 'category' => 'backend', 'level' => 'advanced'],
                ['name' => 'PHP', 'category' => 'backend', 'level' => 'intermediate'],
            ],
            $this->decode()['skills'],
            'duplicates collapse, and the category/level survive',
        );

        $this->em->clear();
        $names = array_map(
            static fn (Skill $s): string => $s->getName(),
            $this->em->getRepository(Skill::class)->findAll(),
        );
        sort($names);
        self::assertSame(['PHP', 'Symfony'], $names, 'Doctrine was removed, PHP added');
    }

    public function testBlankSkillsAreRejected(): void
    {
        $this->client->request('PUT', '/api/admin/skills', server: $this->authHeaders(), content: json_encode([
            'skills' => [
                ['name' => 'Symfony', 'category' => 'backend', 'level' => 'advanced'],
                ['name' => '', 'category' => 'backend', 'level' => 'advanced'],
            ],
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testTheProfileResponseIsShapedAsTheClientExpects(): void
    {
        // The Vue client reads data.profile; the endpoint used to return the
        // bare entity, so data.profile was always undefined.
        $this->client->request('PUT', '/api/admin/profile', server: $this->authHeaders(), content: json_encode([
            'email' => 'new@example.test',
            'phone' => '+49 999',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $body = $this->decode();
        self::assertArrayHasKey('profile', $body);
        self::assertSame('new@example.test', $body['profile']['email']);
    }
}
