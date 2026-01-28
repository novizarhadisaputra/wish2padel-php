<?php

namespace App\Controllers;

class MediaController
{
    public function gallery()
    {
        $conn = getDBConnection();
        
        $medias = null;
        if ($conn) {
            $medias = $conn->query("SELECT * FROM media ORDER BY created_at DESC");
        }
        
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

        $categories = null;
        $media = null;

        if ($conn && $media_id) {
            $stmt = $conn->prepare("SELECT * FROM category WHERE media_id = ? ORDER BY created_at DESC");
            if ($stmt) {
                $stmt->bind_param("i", $media_id);
                $stmt->execute();
                $categories = $stmt->get_result();
                $stmt->close();
            }
            
            $stmt2 = $conn->prepare("SELECT name FROM media WHERE id = ?");
            if ($stmt2) {
                $stmt2->bind_param("i", $media_id);
                $stmt2->execute();
                $media = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();
            }
        }

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

        $photos = null;
        $category = null;

        if ($conn && $category_id) {
            $stmt = $conn->prepare("SELECT * FROM photo WHERE category_id = ? ORDER BY created_at DESC");
            if ($stmt) {
                $stmt->bind_param("i", $category_id);
                $stmt->execute();
                $photos = $stmt->get_result();
                $stmt->close();
            }

            $stmt2 = $conn->prepare("SELECT c.*, m.name AS media_name FROM category c JOIN media m ON c.media_id=m.id WHERE c.id = ?");
            if ($stmt2) {
                $stmt2->bind_param("i", $category_id);
                $stmt2->execute();
                $category = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();
            }
        }
        
        view('media.photos', compact('photos', 'category_id', 'category'));
    }
}
