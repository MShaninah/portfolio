<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\CvStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CvController extends AbstractController
{
    public function __construct(
        private readonly CvStorage $cv,
    ) {
    }

    #[Route('/api/cv', name: 'api_cv_download', methods: ['GET'])]
    public function download(): Response
    {
        if (!$this->cv->exists()) {
            return $this->json(['message' => 'No CV has been uploaded yet.'], Response::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($this->cv->path());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $this->cv->downloadName(),
        );
        // The file changes only when a new CV is uploaded, and the response is
        // public, so let shared caches hold it briefly.
        $response->setPublic();
        $response->setMaxAge(300);

        return $response;
    }

    #[Route('/api/cv', name: 'api_cv_head', methods: ['HEAD'])]
    public function exists(): JsonResponse
    {
        return new JsonResponse(
            null,
            $this->cv->exists() ? Response::HTTP_OK : Response::HTTP_NOT_FOUND,
        );
    }

    #[Route('/api/admin/cv', name: 'api_admin_cv_upload', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile) {
            return $this->json(
                ['message' => 'Send the PDF as multipart/form-data under the field name "file".'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        if (!$file->isValid()) {
            return $this->json(['message' => $file->getErrorMessage()], Response::HTTP_BAD_REQUEST);
        }

        if ($file->getSize() > CvStorage::MAX_BYTES) {
            return $this->json(
                ['message' => \sprintf('The CV must be at most %d MB.', CvStorage::MAX_BYTES >> 20)],
                Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
            );
        }

        // getMimeType() inspects the file's contents, so a renamed .exe cannot
        // pass by claiming a PDF content type in the request.
        if ('application/pdf' !== $file->getMimeType()) {
            return $this->json(
                ['message' => 'Only PDF files are accepted.'],
                Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
            );
        }

        try {
            $this->cv->store($file);
        } catch (FileException $e) {
            // Almost always a permissions problem on the storage directory
            // rather than anything wrong with the file, so say which.
            return $this->json(
                ['message' => 'The CV could not be stored: '.$e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return $this->json([
            'message' => 'CV updated',
            'filename' => $this->cv->downloadName(),
            'updated_at' => $this->cv->updatedAt()?->format(\DATE_ATOM),
        ]);
    }
}
