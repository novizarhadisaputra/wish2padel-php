<?php

namespace App\Controllers;

class MediaController
{
    public function gallery()
    {
        $conn = getDBConnection();
        
        // Fetch media for user view
        $medias = $conn->query("SELECT * FROM media ORDER BY created_at DESC");
        
        view('media.gallery', compact('medias'));
    }

    public function categories()
    {
        $conn = getDBConnection();
        $media_id = $_GET['media_id'] ?? null;

        if (!$media_id) {
             header("Location: " . asset('media/gallery'));
             exit;
        }

        // Fetch categories for this media
        // Legacy: SELECT * FROM category WHERE media_id=?
        $stmt = $conn->prepare("SELECT * FROM category WHERE media_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $media_id);
        $stmt->execute();
        $categories = $stmt->get_result();
        
        // Also fetch media name for breadcrumb/title if needed
        $stmt2 = $conn->prepare("SELECT name FROM media WHERE id = ?");
        $stmt2->bind_param("i", $media_id);
        $stmt2->execute();
        $media = $stmt2->get_result()->fetch_assoc();

        view('media.categories', compact('categories', 'media_id', 'media'));
    }

    public function photos()
    {
        $conn = getDBConnection();
        $category_id = $_GET['category_id'] ?? null;
        
        if (!$category_id) {
             header("Location: " . asset('media/gallery'));
             exit;
        }

        // Fetch photos
        // Legacy: SELECT * FROM photo WHERE category_id=?
        $stmt = $conn->prepare("SELECT * FROM photo WHERE category_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $photos = $stmt->get_result();

        // Fetch category info for breadcrumb
        // Legacy: SELECT c.*, m.name AS media_name FROM category c JOIN media m ...
        $stmt2 = $conn->prepare("SELECT c.*, m.name AS media_name FROM category c JOIN media m ON c.media_id=m.id WHERE c.id = ?");
        $stmt2->bind_param("i", $category_id);
        $stmt2->execute();
        $category = $stmt2->get_result()->fetch_assoc();
        
        view('media.photos', compact('photos', 'category_id', 'category'));
    }
}
