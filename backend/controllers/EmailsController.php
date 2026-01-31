<?php

require_once __DIR__ . '/../services/ResendReceiver.php';

class EmailsController
{
    private function getReceiver(): \ControlCenter\ResendReceiver
    {
        global $con;
        return new \ControlCenter\ResendReceiver($con);
    }

    /**
     * GET /v2/emails
     */
    public function list(Request $request, Response $response): void
    {
        $receiver = $this->getReceiver();

        $options = [
            'folder' => $request->input('folder', 'inbox'),
            'limit' => (int) $request->input('limit', 50),
            'offset' => (int) $request->input('offset', 0),
            'search' => $request->input('search'),
            'include_deleted' => $request->input('include_deleted') === 'true',
        ];

        $result = $receiver->getEmails($options);
        $response->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * GET /v2/emails/{id}
     */
    public function get(Request $request, Response $response): void
    {
        $receiver = $this->getReceiver();
        $id = (int) $request->params['id'];

        if (!$id) {
            $response->error('Email ID required', 400);
            return;
        }

        $email = $receiver->getEmail($id);

        if (!$email) {
            $response->error('Email not found', 404);
            return;
        }

        if ($request->input('mark_read') !== 'false' && $request->input('mark_read') !== null) {
            $receiver->markAsRead($id, true);
            $email['is_read'] = 1;
        }

        $response->json([
            'success' => true,
            'data' => $email,
        ]);
    }

    /**
     * GET /v2/emails/attachments/{id}
     */
    public function getAttachment(Request $request, Response $response): void
    {
        $receiver = $this->getReceiver();
        $id = (int) $request->params['id'];

        if (!$id) {
            $response->error('Attachment ID required', 400);
            return;
        }

        $attachment = $receiver->getAttachmentContent($id);

        if (!$attachment) {
            $response->error('Attachment not found', 404);
            return;
        }

        if ($request->input('download') === 'true') {
            header('Content-Type: ' . $attachment['content_type']);
            header('Content-Disposition: attachment; filename="' . $attachment['filename'] . '"');
            header('Content-Length: ' . $attachment['size']);
            echo $attachment['content'];
            exit;
        }

        $response->json([
            'success' => true,
            'data' => [
                'id' => $attachment['id'],
                'filename' => $attachment['filename'],
                'content_type' => $attachment['content_type'],
                'size' => $attachment['size'],
                'content_base64' => base64_encode($attachment['content']),
            ],
        ]);
    }

    /**
     * POST /v2/emails/{id}/read
     */
    public function markRead(Request $request, Response $response): void
    {
        $receiver = $this->getReceiver();
        $id = (int) $request->params['id'];
        $read = (bool) $request->input('read', true);

        if (!$id) {
            $response->error('Email ID required', 400);
            return;
        }

        $result = $receiver->markAsRead($id, $read);
        $response->json(['success' => $result]);
    }

    /**
     * POST /v2/emails/{id}/starred
     */
    public function markStarred(Request $request, Response $response): void
    {
        $receiver = $this->getReceiver();
        $id = (int) $request->params['id'];
        $starred = (bool) $request->input('starred', true);

        if (!$id) {
            $response->error('Email ID required', 400);
            return;
        }

        $result = $receiver->markAsStarred($id, $starred);
        $response->json(['success' => $result]);
    }

    /**
     * POST /v2/emails/{id}/move
     */
    public function move(Request $request, Response $response): void
    {
        $receiver = $this->getReceiver();
        $id = (int) $request->params['id'];
        $folder = $request->input('folder', 'inbox');

        if (!$id) {
            $response->error('Email ID required', 400);
            return;
        }

        $result = $receiver->moveToFolder($id, $folder);
        $response->json(['success' => $result]);
    }

    /**
     * DELETE /v2/emails/{id}
     */
    public function delete(Request $request, Response $response): void
    {
        $receiver = $this->getReceiver();
        $id = (int) $request->params['id'];
        $permanent = (bool) $request->input('permanent', false);

        if (!$id) {
            $response->error('Email ID required', 400);
            return;
        }

        $result = $receiver->deleteEmail($id, $permanent);
        $response->json(['success' => $result]);
    }

    /**
     * POST /v2/emails/bulk
     */
    public function bulkAction(Request $request, Response $response): void
    {
        $receiver = $this->getReceiver();
        $ids = $request->input('ids', []);
        $bulkAction = $request->input('action', '');

        if (empty($ids)) {
            $response->error('Email IDs required', 400);
            return;
        }

        $success = 0;
        $failed = 0;

        foreach ($ids as $id) {
            $result = false;
            switch ($bulkAction) {
                case 'mark_read':
                    $result = $receiver->markAsRead((int) $id, true);
                    break;
                case 'mark_unread':
                    $result = $receiver->markAsRead((int) $id, false);
                    break;
                case 'delete':
                    $result = $receiver->deleteEmail((int) $id);
                    break;
                case 'move':
                    $folder = $request->input('folder', 'inbox');
                    $result = $receiver->moveToFolder((int) $id, $folder);
                    break;
                case 'star':
                    $result = $receiver->markAsStarred((int) $id, true);
                    break;
                case 'unstar':
                    $result = $receiver->markAsStarred((int) $id, false);
                    break;
            }

            if ($result) {
                $success++;
            } else {
                $failed++;
            }
        }

        $response->json([
            'success' => $failed === 0,
            'processed' => $success,
            'failed' => $failed,
        ]);
    }

    /**
     * GET /v2/emails/stats
     */
    public function stats(Request $request, Response $response): void
    {
        $receiver = $this->getReceiver();
        $stats = $receiver->getFolderStats();
        $response->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * POST /v2/emails/import-raw
     */
    public function importRaw(Request $request, Response $response): void
    {
        $rawEmail = file_get_contents('php://input');

        if (empty($rawEmail)) {
            $response->error('Raw email content required', 400);
            return;
        }

        $receiver = $this->getReceiver();
        $result = $receiver->processRawEmail($rawEmail);
        $response->json($result);
    }

    /**
     * POST /v2/emails/test-webhook
     */
    public function testWebhook(Request $request, Response $response): void
    {
        $payload = file_get_contents('php://input');
        $receiver = $this->getReceiver();
        $result = $receiver->processSnsNotification($payload);
        $response->json($result);
    }
}
