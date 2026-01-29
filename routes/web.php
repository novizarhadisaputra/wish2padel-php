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

// Forgot Password
Router::get('/forgot-password', 'AuthController@forgotPassword');
Router::post('/forgot-password', 'AuthController@postForgotPassword');

Router::get('/admin/dashboard', 'Admin\DashboardController@index');

// Migrated Routes
Router::get('/about-league', 'LeagueController@about');
Router::get('/regis', 'LeagueController@registration');
Router::get('/tournament', 'TournamentController@show');
Router::get('/club', 'ClubController@index');
Router::get('/news', 'PageController@news');
Router::get('/sponsors', 'PageController@sponsors');
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

// Tournament Registration
Router::get('/tournament-register', 'TournamentRegistrationController@index');
Router::post('/tournament-register', 'TournamentRegistrationController@store');

// Media Routes
Router::get('/media/gallery', 'MediaController@gallery');
Router::get('/media/categories', 'MediaController@categories');
Router::get('/media/photos', 'MediaController@photos');

// Admin Core Pages Migration
Router::get('/admin/personnel', 'Admin\AdminController@personnel');
Router::get('/admin/division', 'Admin\AdminController@division');
Router::post('/admin/division', 'Admin\AdminController@division');

Router::get('/admin/news', 'Admin\AdminController@news');
Router::post('/admin/news', 'Admin\AdminController@news');

Router::get('/admin/sponsors', 'Admin\AdminController@sponsors');
Router::post('/admin/sponsors', 'Admin\AdminController@sponsors');

Router::get('/admin/team', 'Admin\AdminController@teams');
Router::post('/admin/team', 'Admin\AdminController@teams');

// Admin Match & Tournament Migration
Router::get('/admin/matches', 'Admin\AdminController@matches');
Router::post('/admin/matches', 'Admin\AdminController@matches');

Router::get('/admin/pair', 'Admin\AdminController@pair');
Router::post('/admin/pair', 'Admin\AdminController@pair');

Router::get('/admin/result', 'Admin\AdminController@result');
Router::post('/admin/result', 'Admin\AdminController@result');

Router::get('/admin/tournament', 'Admin\AdminController@tournament');
Router::post('/admin/tournament', 'Admin\AdminController@tournament');

Router::get('/admin/playoff', 'Admin\AdminController@playoff');
Router::post('/admin/playoff', 'Admin\AdminController@playoff');

// Admin Miscellaneous Migration
Router::get('/admin/documents', 'Admin\AdminController@documents');
Router::post('/admin/documents', 'Admin\AdminController@documents');

Router::get('/admin/gallery', 'Admin\AdminController@gallery');
Router::post('/admin/gallery', 'Admin\AdminController@gallery');

Router::get('/admin/payment_settings', 'Admin\AdminController@paymentSettings');
Router::post('/admin/payment_settings', 'Admin\AdminController@paymentSettings');

Router::get('/admin/settings', 'Admin\AdminController@settings');
Router::post('/admin/settings', 'Admin\AdminController@settings');

Router::get('/admin/presentation', 'Admin\AdminController@presentation');
Router::post('/admin/presentation', 'Admin\AdminController@presentation');

Router::get('/admin/players', 'Admin\AdminController@players');

Router::get('/admin/users', 'Admin\AdminController@users');
Router::post('/admin/impersonate', 'Admin\AdminController@impersonate');

Router::get('/admin/registrations', 'Admin\AdminController@registrations');

Router::get('/admin/windows', 'Admin\AdminController@windows');
Router::post('/admin/windows', 'Admin\AdminController@windows');

Router::get('/admin/penalties', 'Admin\AdminController@penalties');
Router::post('/admin/penalties', 'Admin\AdminController@penalties');

// Admin Club Management
Router::get('/admin/club', 'Admin\ClubController@index');
Router::get('/admin/club/create', 'Admin\ClubController@create');
Router::post('/admin/club/store', 'Admin\ClubController@store');
Router::get('/admin/club/show', 'Admin\ClubController@show');
Router::get('/admin/club/edit', 'Admin\ClubController@edit');
Router::post('/admin/club/update', 'Admin\ClubController@update');
Router::get('/admin/club/delete', 'Admin\ClubController@delete');

// Club Dashboard Migration
Router::get('/club/dashboard', 'Club\ClubDashboardController@index');
Router::get('/club/team', 'Club\ClubDashboardController@teams');
Router::get('/club/update', 'Club\ClubDashboardController@update');
Router::post('/club/update', 'Club\ClubDashboardController@update');

// Root Files Migration
Router::get('/team', 'TeamController@index');
Router::get('/windows', 'TeamController@windows');
Router::post('/payment/webhook', 'PaymentController@webhook');
Router::get('/payment/webhook', 'PaymentController@webhook');

// Team Actions (formerly auth/*)
Router::get('/submit_lineup', 'TeamController@submitLineup');
Router::post('/submit_lineup', 'TeamController@submitLineup');
Router::get('/submit_score', 'TeamController@submitScore');
Router::post('/submit_score', 'TeamController@submitScore');
