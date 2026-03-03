<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Profile;
use App\Entity\Project;
use App\Entity\Publication;
use App\Entity\Skill;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import-portfolio', description: 'Import fixed portfolio data (projects, skills, publications, profile) into the database from a JSON file.')]
class ImportPortfolioCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'Path to JSON file with portfolio seed data', dirname(__DIR__, 2) . '/config/portfolio.seed.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = (string)$input->getArgument('file');

        if (!is_file($file)) {
            $io->error("Seed file not found: {$file}");
            return Command::FAILURE;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            $io->error('Failed to read seed file.');
            return Command::FAILURE;
        }

        $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        $this->em->beginTransaction();
        try {
            $this->importProfile($data['profile'] ?? [] , $io);
            $this->importSkills($data['skills'] ?? [], $io);
            $this->importPublications($data['publications'] ?? [], $io);
            $this->importProjects($data['projects'] ?? [], $io);

            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $io->success('Portfolio data imported successfully.');
        return Command::SUCCESS;
    }

    private function importProfile(array $profile, SymfonyStyle $io): void
    {
        if (empty($profile)) {
            $io->writeln('- No profile data');
            return;
        }
        $repo = $this->em->getRepository(Profile::class);
        $entity = $repo->findOneBy([]) ?? new Profile();
        if (!empty($profile['email'])) {
            $entity->setEmail((string)$profile['email']);
        }
        $entity->setPhone(isset($profile['phone']) ? (string)$profile['phone'] : null);
        $this->em->persist($entity);
        $io->writeln('- Profile upserted');
    }

    private function importSkills(array $skills, SymfonyStyle $io): void
    {
        if (!$skills) { $io->writeln('- No skills'); return; }
        $repo = $this->em->getRepository(Skill::class);
        foreach ($skills as $name) {
            $name = (string)$name;
            if ($name === '') { continue; }
            $entity = $repo->findOneBy(['name' => $name]) ?? (new Skill())->setName($name);
            // ensure name (in case of case changes)
            $entity->setName($name);
            $this->em->persist($entity);
        }
        $io->writeln(sprintf('- %d skills upserted', count($skills)));
    }

    private function importPublications(array $publications, SymfonyStyle $io): void
    {
        if (!$publications) { $io->writeln('- No publications'); return; }
        $repo = $this->em->getRepository(Publication::class);
        $count = 0;
        foreach ($publications as $p) {
            $title = (string)($p['title'] ?? '');
            $year = isset($p['year']) ? (string)$p['year'] : null;
            if ($title === '') { continue; }
            $entity = $repo->findOneBy(['title' => $title, 'year' => $year]) ?? new Publication();
            $entity->setTitle($title);
            $entity->setYear($year);
            $entity->setUrl(isset($p['url']) ? (string)$p['url'] : null);
            $this->em->persist($entity);
            $count++;
        }
        $io->writeln(sprintf('- %d publications upserted', $count));
    }

    private function importProjects(array $projects, SymfonyStyle $io): void
    {
        if (!$projects) { $io->writeln('- No projects'); return; }
        $repo = $this->em->getRepository(Project::class);
        $count = 0;
        foreach ($projects as $p) {
            $slug = (string)($p['id'] ?? $p['slug'] ?? '');
            if ($slug === '') { continue; }
            $entity = $repo->findOneBy(['slug' => $slug]) ?? new Project();
            $entity->setSlug($slug);
            $entity->setTitle(isset($p['title']) ? (string)$p['title'] : null);
            $entity->setTagline(isset($p['tagline']) ? (string)$p['tagline'] : null);
            $entity->setSummary(isset($p['summary']) ? (string)$p['summary'] : null);

            // Map stack to highlights if provided
            $highlights = $p['highlights'] ?? ($p['stack'] ?? null);
            $entity->setHighlights(is_array($highlights) ? array_values($highlights) : null);

            // Normalize links to label/href pairs only (icons are frontend concern)
            $links = $p['links'] ?? null;
            if (is_array($links)) {
                $normLinks = [];
                foreach ($links as $l) {
                    if (is_array($l)) {
                        $normLinks[] = [
                            'label' => (string)($l['label'] ?? ''),
                            'href' => (string)($l['href'] ?? ''),
                        ];
                    }
                }
                $entity->setLinks($normLinks);
            } else {
                $entity->setLinks(null);
            }

            $entity->setYear(isset($p['year']) ? (string)$p['year'] : null);
            $entity->setCategory(isset($p['category']) ? (string)$p['category'] : null);

            $this->em->persist($entity);
            $count++;
        }
        $io->writeln(sprintf('- %d projects upserted', $count));
    }
}
