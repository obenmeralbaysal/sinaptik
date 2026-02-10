<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\XmlSource;

class XmlSourceController extends Controller
{
    public function index()
    {
        $user = User::findById($_SESSION['user_id']);
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $sources = XmlSource::findAllByUser($user['id']);

        $this->render('xml_sources/index', [
            'title' => 'XML Data Sources',
            'active_page' => 'xml_sources',
            'sources' => $sources
        ]);
    }

    public function create()
    {
        $this->render('xml_sources/create', [
            'title' => 'Add XML Source',
            'active_page' => 'xml_sources'
        ]);
    }

    public function store()
    {
        $user = User::findById($_SESSION['user_id']);
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $data = [
            'user_id' => $user['id'],
            'name' => $_POST['name'],
            'url' => $_POST['url'],
            'selector' => $_POST['selector'],
            'template' => $_POST['template'],
            'schedule_time' => $_POST['schedule_time'],
            'status' => 'active'
        ];

        XmlSource::create($data);

        header('Location: /xml-sources');
        exit;
    }
}
