<?php

class BookmarksController
{
    /**
     * GET /v2/bookmarks
     */
    public function getAll(Request $request, Response $response): void
    {
        $bookmarks = query("SELECT * FROM control_center_bookmarks WHERE userID='{$request->userID}'");
        $json = [];
        $i = 0;
        foreach ($bookmarks as $b) {
            $json[$i]['id'] = $b['id'];
            $json[$i]['icon'] = $b['icon'];
            $json[$i]['title'] = $b['title'];
            $json[$i]['location'] = $b['location'];
            $i++;
        }
        $response->json($json);
    }

    /**
     * POST /v2/bookmarks
     */
    public function create(Request $request, Response $response): void
    {
        $title = escape_string($request->input('title', ''));
        $location = escape_string($request->input('location', ''));
        $icon = escape_string($request->input('icon', ''));

        if (empty($title) || empty($location)) {
            $response->error('title and location are required', 400);
            return;
        }

        if (query("INSERT INTO control_center_bookmarks VALUES (0, '$icon', '$title', '$location', '{$request->userID}')")) {
            $response->success([], 'Bookmark created');
        } else {
            $response->error('Failed to create bookmark', 500);
        }
    }

    /**
     * DELETE /v2/bookmarks
     */
    public function delete(Request $request, Response $response): void
    {
        $location = escape_string($request->input('location', ''));

        if (empty($location)) {
            $response->error('location is required', 400);
            return;
        }

        if (query("DELETE FROM control_center_bookmarks WHERE location='$location' AND userID='{$request->userID}'")) {
            $response->success([], 'Bookmark deleted');
        } else {
            $response->error('Failed to delete bookmark', 500);
        }
    }

    /**
     * GET /v2/bookmarks/check?location=...
     */
    public function check(Request $request, Response $response): void
    {
        $location = escape_string($request->input('location', ''));

        if (empty($location)) {
            $response->error('location is required', 400);
            return;
        }

        $checkQuery = query("SELECT * FROM control_center_bookmarks WHERE location='$location' AND userID='{$request->userID}'");
        $response->json(['bookmarked' => mysqli_num_rows($checkQuery) == 1]);
    }
}
