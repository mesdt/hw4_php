<?php
use Doctrine\DBAL\Connection;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Для php -S host:port -t web web/index.php, чтобы статика отдавалась
/*if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . preg_replace('/(\?.*)$/', '', $_SERVER['REQUEST_URI']))) {
    return false;
}*/

require_once __DIR__ . '/../vendor/autoload.php';

Request::enableHttpMethodParameterOverride();

$sapp = (new Application(['debug' => true]))
    // шаблонизатор
    ->register(new TwigServiceProvider(),
        ['twig.path' => __DIR__ . '/../views'])
    // база
    ->register(new DoctrineServiceProvider(),
        ['db.options' => ['driver' => 'pdo_mysql', 'dbname' => 'hw1', 'charset' => 'utf8']]);

$sapp->get('/', function (Application $app) {
    /**@var $conn Connection */
    $conn = $app['db'];
    $students = $conn->fetchAll('select * from students');
    return $app['twig']->render('students.twig', ['students' => $students]);
});

$sapp->get('/students/{id}', function (Application $app, $id) {
    /**@var $conn Connection */
    $conn = $app['db'];
    $student = $conn->fetchAssoc('select * from students where id = ?', [$id]);
    if (!$student) {
        throw new NotFoundHttpException("Нет такого студента - $id");
    }
    $subjects = $conn->fetchAll('select * from subjects');
    $scores = $conn->fetchAll('select * from scores where student_id = ?', [$id]);
    $scorez = [];
    foreach ($scores as $score) {
        $scorez[$score['subject_id']] = $score['score'];
    }
    return $app['twig']->render('student.twig', ['student' => $student, 'subjects' => $subjects, 'scorez' => $scorez]);
});

$sapp->post('/students', function (Application $app, Request $req) {
	/**@var $conn Connection */
	$conn = $app['db'];
	$name = $req->get('name');
	$conn->insert('students', ['name' => $name]);
	return $app->redirect('/');
});

$sapp->delete('/students/{id}', function (Application $app, $id) {
    /**@var $conn Connection */
    $conn = $app['db'];
    $conn->delete('students', ['id' => $id]);
    return $app->redirect('/');
});

$sapp->put('/students/{id}/scores', function (Application $app, Request $req, $id) {
    /**@var $conn Connection */
    $conn = $app['db'];
    $conn->transactional(function (Connection $conn) use ($id, $req) {
        $conn->delete('scores', ['student_id' => $id]);
        foreach ($req->get('scores') as $subject_id => $score) {
            if ($score) {
                $conn->insert('scores', ['student_id' => $id, 'subject_id' => $subject_id, 'score' => $score]);
            }
        }
    });
    return $app->redirect("/students/$id");
});

/* Lab 4 ajax */
use Symfony\Component\HttpFoundation\ParameterBag;

$sapp->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});

$sapp->delete('/students', function (Request $request) use ($sapp) {
    /**@var $conn Connection */
    $conn = $sapp['db'];
	$id = $request->request->get('id');
    $conn->delete('students', ['id' => $id]);
    return $sapp->json("Удаление произведено успешно", 200);
});

$sapp->get('/students/{id}/grades', function ($id) use ($sapp) {
    /**@var $conn Connection */
    $conn = $sapp['db'];
	$subjects = $conn->fetchAll('select * from subjects');
    $scores = $conn->fetchAll('select * from scores where student_id = ?', [$id]);
    $scorez = [];
    foreach ($scores as $score) {
        $scorez[$score['subject_id']] = $score['score'];
    }
    return $sapp->json(array('subjects'=>$subjects,'scorez'=>$scorez), 200);
});

$sapp->run();
