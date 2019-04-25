<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Controller;

use Smart\CoreBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends Controller
{
    public function syslogAction(Request $request): Response
    {
        $json = [
            'status'  => 'success',
            'message' => '',
            'data'    => [],
        ];

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $allowKeys = $this->getParameter('cms_dev_api');

        $key = $request->query->get('key');

        if (!in_array($key, $allowKeys)) {
            $json = [
                'status'  => 'error',
                'message' => 'Access denied',
                'data'    => [],
            ];

            return $this->json($json, 403);
        }

        //$command = $request->query->get('command');

        $data = [];

        $lastSyslog = $em->getRepository('CMSBundle:Syslog')->findOneBy([], ['id' => 'DESC']);

        if ($lastSyslog) {
            $data['last_syslog_datetime'] = $lastSyslog->getCreatedAt()->format('Y-m-d H:i:s');
            $data['last_syslog_id']       = $lastSyslog->getId();
        } else {
            $data['last_syslog_id'] = null;
        }

        $json['data'] = $data;

        return $this->json($json);
    }

    public function getDatabaseDumpAction(Request $request)
    {
        $allowKeys = $this->getParameter('cms_dev_api');

        $key = $request->query->get('key');

        if (!in_array($key, $allowKeys)) {
            $json = [
                'status'  => 'error',
                'message' => 'Access denied',
                'data'    => [],
            ];

            return $this->json($json, 403);
        }

        $DBUSER   = $this->getParameter('database_user');
        $DBPASSWD = $this->getParameter('database_password');
        $DATABASE = $this->getParameter('database_name');

        $filename = "backup-" . date("d-m-Y") . ".sql.gz";
        $mime = "application/x-gzip";

        header( "Content-Type: " . $mime );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

        passthru("mysqldump -u $DBUSER --password=$DBPASSWD $DATABASE | gzip --best");

        exit(0);
    }
}
