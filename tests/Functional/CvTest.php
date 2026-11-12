<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Service\CvStorage;
use App\Tests\ResetsRateLimiters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final class CvTest extends WebTestCase
{
    use ResetsRateLimiters;

    private const string PASSWORD = 'test-password';
    private const string IP = '198.51.100.90';

    private KernelBrowser $client;
    private CvStorage $storage;

    protected function setUp(): void
    {
        $this->client = static::createClient([], ['REMOTE_ADDR' => self::IP]);
        $this->storage = static::getContainer()->get(CvStorage::class);
        $this->resetRateLimiters(self::IP);

        // This test hits /api/portfolio, so it needs a schema of its own rather
        // than whatever a previously-run test happened to leave behind.
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($em);
        $classes = $em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($classes);
        $tool->createSchema($classes);

        // Each test starts with no stored CV.
        foreach ([$this->storage->path(), \dirname($this->storage->path()).'/cv.name'] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
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

    private function upload(string $filename, string $contents): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cv');
        self::assertIsString($tmp);
        file_put_contents($tmp, $contents);

        $this->client->request(
            'POST',
            '/api/admin/cv',
            files: ['file' => new UploadedFile($tmp, $filename, null, null, true)],
            server: ['HTTP_AUTHORIZATION' => 'Bearer '.$this->token()],
        );
    }

    private static function pdfBytes(): string
    {
        // Enough of a PDF for finfo to report application/pdf.
        return "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
    }

    public function testDownloadIs404WhenNoCvHasBeenUploaded(): void
    {
        $this->client->request('GET', '/api/cv');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUploadRequiresAuthentication(): void
    {
        $this->client->request('POST', '/api/admin/cv');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAPdfCanBeUploadedAndDownloaded(): void
    {
        $this->upload('Lebenslauf_Mohamad.pdf', self::pdfBytes());

        self::assertResponseIsSuccessful();
        self::assertTrue($this->storage->exists());

        $this->client->request('GET', '/api/cv');

        self::assertResponseIsSuccessful();
        self::assertSame('application/pdf', $this->client->getResponse()->headers->get('Content-Type'));
        self::assertStringContainsString(
            'Lebenslauf_Mohamad.pdf',
            (string) $this->client->getResponse()->headers->get('Content-Disposition'),
        );
        self::assertStringContainsString('attachment', (string) $this->client->getResponse()->headers->get('Content-Disposition'));
    }

    public function testUploadingAgainReplacesThePreviousCv(): void
    {
        $this->upload('first.pdf', self::pdfBytes());
        $this->upload('second.pdf', self::pdfBytes()."% second\n");

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('second', (string) file_get_contents($this->storage->path()));
        self::assertSame('second.pdf', $this->storage->downloadName());
    }

    public function testANonPdfIsRejected(): void
    {
        // A renamed executable must not slip through on its filename alone.
        $this->upload('cv.pdf', "MZ\x90\x00this is not a pdf at all, it is a binary\n");

        self::assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        self::assertFalse($this->storage->exists(), 'a rejected upload must not be stored');
    }

    public function testMissingFileFieldIsABadRequest(): void
    {
        $this->client->request(
            'POST',
            '/api/admin/cv',
            server: ['HTTP_AUTHORIZATION' => 'Bearer '.$this->token()],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testThePortfolioPayloadReportsCvAvailability(): void
    {
        $this->client->request('GET', '/api/portfolio');
        $before = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertFalse($before['cv']['available']);

        $this->upload('cv.pdf', self::pdfBytes());

        $this->client->request('GET', '/api/portfolio');
        $after = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertTrue($after['cv']['available']);
        self::assertSame('cv.pdf', $after['cv']['filename']);
    }
}
