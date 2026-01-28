<?php

namespace App\Controllers;

class PageController
{
    public function news()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        
        $result = null;
        if ($conn) {
            $stmt = $conn->prepare("SELECT id, title, description, image, created_at FROM blog_news ORDER BY created_at DESC LIMIT 8");
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();
            }
        }
        
        view('pages.news', compact('result'));
    }

    public function newsDetail()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        $id = $_GET['id'] ?? 0;
        $id = intval($id);
        
        $news = null;
        if ($conn) {
            $stmt = $conn->prepare("SELECT title, description, image, created_at FROM blog_news WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $news = $result ? $result->fetch_assoc() : null;
                $stmt->close();
            }
        }
        
        if (!$news && $conn) {
            echo "<div class='container py-5'><div class='alert alert-danger'>News not found.</div></div>";
            return;
        }
        
        view('pages.news_detail', compact('news'));
    }

    public function sponsors()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        
        $premiumSponsors = [];
        $standardSponsors = [];
        $resultCollaborators = null;

        if ($conn) {
            $resultSponsors = $conn->query("SELECT * FROM sponsors WHERE status = 'sponsor' ORDER BY sponsor_id DESC");
            $resultCollaborators = $conn->query("SELECT * FROM sponsors WHERE status = 'collaborate' ORDER BY sponsor_id DESC");
            
            if ($resultSponsors) {
                while ($row = $resultSponsors->fetch_assoc()) {
                    if (isset($row['type']) && $row['type'] === 'premium') {
                        $premiumSponsors[] = $row;
                    } else {
                        $standardSponsors[] = $row;
                    }
                }
            }
        }
        
        view('pages.sponsors', compact('premiumSponsors', 'standardSponsors', 'resultCollaborators'));
    }

    public function documents()
    {
        $conn = getDBConnection();
        $section1 = [];
        $section2 = [];

        if ($conn) {
            $query = "SELECT id, file_path FROM documents ORDER BY id ASC";
            $result = mysqli_query($conn, $query);

            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    if ($row['id'] <= 2) {
                        $section1[] = $row;
                    } else {
                        $section2[] = $row;
                    }
                }
            }
        }
        view('pages.documents', compact('section1', 'section2'));
    }
}
