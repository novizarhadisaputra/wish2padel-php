<?php

namespace App\Controllers;

class PageController
{
    public function news()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        
        $stmt = $conn->prepare("SELECT id, title, description, image, created_at FROM blog_news ORDER BY created_at DESC LIMIT 8");
        $stmt->execute();
        $result = $stmt->get_result();
        
        view('pages.news', compact('result'));
    }

    public function newsDetail()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        $id = $_GET['id'] ?? 0;
        $id = intval($id);
        
        $stmt = $conn->prepare("SELECT title, description, image, created_at FROM blog_news WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $news = $result->fetch_assoc();
        
        if (!$news) {
            echo "<div class='container py-5'><div class='alert alert-danger'>News not found.</div></div>";
            return;
        }
        
        view('pages.news_detail', compact('news'));
    }

    public function sponsors()
    {
        $conn = getDBConnection();
        $username = $_SESSION['username'] ?? null;
        
        $resultSponsors = $conn->query("SELECT * FROM sponsors WHERE status = 'sponsor' ORDER BY sponsor_id DESC");
        $resultCollaborators = $conn->query("SELECT * FROM sponsors WHERE status = 'collaborate' ORDER BY sponsor_id DESC");
        
        $premiumSponsors = [];
        $standardSponsors = [];
        
        while ($row = $resultSponsors->fetch_assoc()) {
            if (isset($row['type']) && $row['type'] === 'premium') {
                $premiumSponsors[] = $row;
            } else {
                $standardSponsors[] = $row;
            }
        }
        
        view('pages.sponsors', compact('premiumSponsors', 'standardSponsors', 'resultCollaborators'));
    }

    public function documents()
    {
        $conn = getDBConnection();
        $query = "SELECT id, file_path FROM documents ORDER BY id ASC";
        $result = mysqli_query($conn, $query);

        $section1 = [];
        $section2 = [];

        while ($row = mysqli_fetch_assoc($result)) {
            if ($row['id'] <= 2) {
                $section1[] = $row;
            } else {
                $section2[] = $row;
            }
        }
        view('pages.documents', compact('section1', 'section2'));
    }
}
