<?php

/*
** PHPUnit setup for Unit Tests
*/

require_once __DIR__ . '/../backend/src/Services/Matcher.php';
require_once __DIR__ . '/../backend/src/Controllers/Health.php';
require_once __DIR__ . '/../backend/src/Controllers/MatchEndpoint.php';
require_once __DIR__ . '/../backend/src/Controllers/NotifyEndpoint.php';
require_once __DIR__ . '/../backend/src/Services/PasswordPolicy.php';
require_once __DIR__ . '/../backend/src/Core/Auth.php';
require_once __DIR__ . '/../backend/src/Controllers/AuthEndpoint.php';
require_once __DIR__ . '/../backend/src/Controllers/HistoryEndpoint.php';
require_once __DIR__ . '/../backend/src/Controllers/ExploreEndpoint.php';
require_once __DIR__ . '/../backend/src/Controllers/FavoritesEndpoint.php';
require_once __DIR__ . '/../backend/src/Controllers/ProfileEndpoint.php';
require_once __DIR__ . '/../backend/src/Controllers/PasswordResetEndpoint.php';
require_once __DIR__ . '/../backend/src/Controllers/EmailVerificationEndpoint.php';
require_once __DIR__ . '/../backend/src/Services/Embeddings.php';
require_once __DIR__ . '/../backend/src/Controllers/SearchEndpoint.php';
