<?php
namespace Api;

class ClassController
{
    static $CANVAS_BASE_URL = "https://canvas.instructure.com/";
    static $TOKEN = "5590~WQTFigoJTgyTsxxJvq3MZbFhemlTtIUnCqELPlv2XCslY9UbRi8zuJnqEkK4dL1M";
    private $requestMethod;
    private $path;

    public function __construct($requestMethod, $path)
    {
        $this->requestMethod = $requestMethod;
        $this->path = $path;
    }

    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                if ($this->path === "/allClass") {
                    $response = $this->getAllClass();
                }
                if ($this->path === "/schedule") {
                    $response = $this->generateSchedule();
                }
                break;
            default:
                $response = null;
                break;
        }

        if ($response) {
            header('HTTP/1.1 200 OK');
        } else {
            header('HTTP/1.1 404 Not Found');
        }
        echo json_encode($response);
    }

    private function generateSchedule()
    {
        $classes = $this->getAllClass();
        $schedule = array();
        foreach ($classes as $class) {
            $classId = $class["id"];
            $classCode = $class["course_code"];
            $className = $class["name"];

            array_push(
                $schedule,
                array(
                    "classCode" => $classCode,
                    "className" => $className,
                    "classId" => $classId,
                    "assignments" => $this->getOneClassUpcomingAssignments($classId),
                )
            );
        }

        return $schedule;
    }

    private function getAllClass()
    {
        $path = "/api/v1/courses?enrollment_state=active";
        try {
            $allClass = $this->getCanvasApi($path);
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }

        return $allClass;
    }

    private function getOneClassUpcomingAssignments($class_id)
    {
        $path = '/api/v1/courses/' . $class_id . '/assignments?order_by=due_at&bucket=future';
        try {
            $allAssignments = $this->getCanvasApi($path);

            $filteredAssignments = array_filter($allAssignments, function ($item) {
                return isset($item['due_at']) && $item['due_at'] !== null;
            });

            $currentTime = new \DateTime(); // get the current time
            $dueIn7Days = array_filter($filteredAssignments, function ($assignment) use ($currentTime) {
                $date = new \DateTime($assignment['due_at']);
                $interval = $currentTime->diff($date);
                return $interval->days <= 7;
            });

            $assignments = array();
            foreach ($dueIn7Days as $assignment) {
                $assignments[] = array(
                    "name" => $assignment['name'],
                    "due_at" => $assignment['due_at'],
                );
            }
            $dueIn7Days = $assignments;


        } catch (\PDOException $e) {
            exit($e->getMessage());
        }

        return $dueIn7Days;
    }

    private function getCanvasApi($path)
    {
        $memcached = new \Memcached();
        $memcached->addServer('localhost', 11211);

        $cacheKey = md5($path);
        $response = $memcached->get($cacheKey);

        if (!$response) {
            $options = array(
                'http' => array(
                    'method' => 'GET',
                    'header' => 'Authorization: Bearer ' . self::$TOKEN
                )
            );
            $context = stream_context_create($options);
            $content = file_get_contents(self::$CANVAS_BASE_URL . $path, false, $context);
            $response = json_decode($content, true);
            $memcached->set($cacheKey, $response, 3600); // Cache for 1 hour
        }

        return $response;
    }

}