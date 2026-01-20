<?php

use App\Core\Router;

// Define your routes here

Router::get('/', 'HomeController@index');

Router::get('/test', function() {
    echo "Router is working!";
});

Router::get('/login', 'AuthController@login');
Router::post('/login', 'AuthController@postLogin');
Router::get('/logout', 'AuthController@logout');

Router::get('/admin/dashboard', 'Admin\DashboardController@index');

// Migrated Routes
Router::get('/about-league', 'LeagueController@about');
Router::get('/regis', 'LeagueController@registration');
Router::get('/tournament', 'TournamentController@show');
Router::get('/club', 'ClubController@index');
Router::get('/news', 'PageController@news');
Router::get('/sponsor', 'PageController@sponsors');
Router::get('/club-detail', 'ClubController@show');
Router::get('/news-detail', 'PageController@newsDetail');
Router::get('/match', 'MatchController@show');
Router::get('/team_profile', 'TeamController@profile');

// New Migrated Routes
Router::get('/leaderboard', 'LeagueController@leaderboard');
Router::get('/ranking', 'LeagueController@ranking');
Router::get('/documents', 'PageController@documents');
Router::get('/register-player', 'PlayerController@register');
Router::post('/register-player', 'PlayerController@register');
Router::get('/payment', 'PaymentController@show');
Router::get('/payment/verify', 'PaymentController@verify');

// Dashboard & Team Routes
Router::get('/dashboard', 'DashboardController@index');
Router::get('/league', 'LeagueController@hub');
Router::get('/myteam', 'TeamController@myTeam');
Router::post('/myteam', 'TeamController@myTeam');
Router::get('/scheduled', 'TeamController@scheduled');

// Club Registration
Router::get('/regis-club', 'ClubController@register');
Router::post('/regis-club', 'ClubController@register');

// Media Routes
Router::get('/media/gallery', 'MediaController@gallery');
Router::get('/media/categories', 'MediaController@categories');
Router::get('/media/photos', 'MediaController@photos');

