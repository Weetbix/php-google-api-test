<?php

namespace App\Controller;

use Google\Client;
use Google\Service\Drive;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TestController extends AbstractController
{
    #[Route('/test', name: 'test')]
    public function test(): Response
    {
        return new Response('Hello World');
    }

    #[Route('/api/sheets', name: 'list_sheets', methods: ['GET'])]
    public function listSheets(Request $request): Response
    {
        // Initialize the Google Client
        $client = new Client();
        $credentialsPath = __DIR__ . '/client_secret.json';
        
        if (!file_exists($credentialsPath)) {
            return new JsonResponse(['error' => 'OAuth credentials not found'], 400);
        }

        $client->setAuthConfig($credentialsPath);
        $client->addScope(Drive::DRIVE_READONLY);
        
        // Check if we have a token in session
        $session = $request->getSession();
        $token = $session->get('google_token');

        if ($token) {
            $client->setAccessToken($token);
            
            if ($client->isAccessTokenExpired()) {
                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    $session->set('google_token', $client->getAccessToken());
                } else {
                    // Redirect to auth instead of returning JSON
                    return $this->redirect($this->generateAuthUrl($client));
                }
            }
        } else {
            // Redirect to auth instead of returning JSON
            return $this->redirect($this->generateAuthUrl($client));
        }

        try {
            $service = new Drive($client);
            
            // Search for Google Sheets files with 'test' in the name
            $optParams = [
                'q' => "name contains 'test' and mimeType='application/vnd.google-apps.spreadsheet'",
                'fields' => 'files(id, name, webViewLink, createdTime)',
            ];
            
            $results = $service->files->listFiles($optParams);
            
            $files = [];
            foreach ($results->getFiles() as $file) {
                $files[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'url' => $file->getWebViewLink(),
                    'created' => $file->getCreatedTime(),
                ];
            }
            
            return new JsonResponse(['files' => $files]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/sheets/auth/callback', name: 'google_auth_callback', methods: ['GET'])]
    public function handleAuthCallback(Request $request): Response
    {
        $client = new Client();
        $credentialsPath = __DIR__ . '/client_secret.json';
        $client->setAuthConfig($credentialsPath);
        $client->addScope(Drive::DRIVE_READONLY);
        $client->setRedirectUri($this->generateUrl('google_auth_callback', [], 0));
        
        if ($code = $request->query->get('code')) {
            $token = $client->fetchAccessTokenWithAuthCode($code);
            if (!isset($token['error'])) {
                $request->getSession()->set('google_token', $token);
                return $this->redirectToRoute('list_sheets');
            }
        }
        
        return new JsonResponse(['error' => 'Authentication failed'], 400);
    }

    private function generateAuthUrl(Client $client): string
    {
        $client->setRedirectUri($this->generateUrl('google_auth_callback', [], 0));
        $client->setAccessType('offline');  // This will get us a refresh token
        $client->setPrompt('consent');      // Force consent screen to ensure we get refresh token
        return $client->createAuthUrl();
    }
}