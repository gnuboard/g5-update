<?php
/**
 * 그누보드5 Router 테스트 페이지
 * @todo Apache서버의 경우, 동일한 경로에 .htaccess 파일을 생성 후 코드를 추가해야 한다.
 * @todo Nginx서버의 경우, 설정파일에 코드를 추가해야한다. (nginx.conf)
 * - 설정파일 검색 명령어 : sudo find / -name nginx.conf 
 * 
 * @link http://3.35.173.159/g5-update/router
 */
/*
    #.htaccess
    # Redirect all to router.php
    #

    RewriteEngine On

    # if a directory or a file exists, use it directly
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d

    RewriteCond %{REQUEST_URI} (/[^.]*|\.)$ [NC]
    RewriteRule .* router.php [L]
*/
/*    
    # nginx.conf
    server {
        ... 
        
        # Router 설정 추가 START
        # 모든 요청에 대해 처리
        location / {
            # 요청에 대한 파일 탐색
            # 우선순위 : $url(1번) $url/(2번) router(3번)
            try_files $uri $uri/ /g5-update/router.php$is_args$args;
        }
        # Router 설정 추가 END
*/

    include_once('./_common.php');

    // In case one is using PHP 5.4's built-in server
    $filename = __DIR__ . preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
    if (php_sapi_name() === 'cli-server' && is_file($filename)) {
        return false;
    }

    // Include the Router class
    // @note: it's recommended to just use the composer autoloader when working with other packages too
    require_once './plugin/router/src/Bramus/Router/Router.php';
    
    // Create a Router
    $router = new \Bramus\Router\Router();
    
    // Custom 404 Handler
    $router->set404(function () {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        // echo '404, route not found!';
    });
    
    // custom 404
    $router->set404('/test(/.*)?', function () {
        // header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        echo '<h1><mark>404, route not found!</mark></h1>';
    });

    $router->set404('/api(/.*)?', function() {
        // header('HTTP/1.1 404 Not Found');
        header('Content-Type: application/json');

        $jsonArray = array();
        $jsonArray['status'] = "404";
        $jsonArray['status_text'] = "route not defined";

        echo json_encode($jsonArray);
    });

    // Before Router Middleware
    $router->before('GET', '/.*', function () {
        header('X-Powered-By: bramus/router');
    });

    // Static route: / (homepage)
    $router->get('/router', function() {
        setRouterMainPage();
    });
    $router->get('/router.php', function() {
        setRouterMainPage();
    });
    // Static route: /board
    $router->get('/board/(\w+)/list(/\d+)?', function ($bo_table, $page) {
        echo "bo_table : {$bo_table} {$page} page <br> QUERY_STRING : " . $_SERVER['QUERY_STRING'];
        echo "<br><br>";

        getBoardList($bo_table, $page, $_SERVER['QUERY_STRING']);
    });

    // Thunderbirds are go!
    $router->run();



function setRouterMainPage()
{
    // base url
    $get_path_url = parse_url(G5_URL);    
    $base_path = isset($get_path_url['path']) ? $get_path_url['path'].'/' : '/';

    echo '<h1>그누보드s</h1>
        <p>게시판<p>
        <ul>
            <li><a href="'.$base_path.'board/free/list/">자유게시판 목록(free)</a></li>
            <li><a href="'.$base_path.'board/free/list/23">자유게시판 목록(free) + 페이지</a></li>
            <li><a href="'.$base_path.'board/free/list/23?type=contents&search=테스트">자유게시판 목록(free) + 페이지 + 검색 </a></li>
        </ul>
        <br><br>
        <p>Custom error routes</p>
        <ul>
            <li><a href="'.$base_path.'something">/*</a> <em>Normal 404</em></li>
            <li><a href="'.$base_path.'g5-update/test">/test/*</a> <em>Custom 404</em></li>
            <li><a href="'.$base_path.'g5-update/api/getUser">/api/getUser</a> <em>API 404</em></li>
        </ul>';
}

function getBoardList($bo_table, $page, $query_string) 
{
    $board = get_board_db($bo_table, true);
    if (!$board['bo_table']) {
        alert('존재하지 않는 게시판입니다.', G5_URL);
    }
    exit;
}
