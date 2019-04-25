<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Controller;

use GuzzleHttp\Client;
use Lsw\ApiCallerBundle\Call\HttpGetJson;
use Monolith\Bundle\CMSBundle\Entity\Syslog;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Smart\CoreBundle\Controller\Controller;
use Smart\CoreBundle\Pagerfanta\SimpleDoctrineORMAdapter;
use SmartCore\Bundle\SettingsBundle\Model\SettingModel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Security("is_granted('ROLE_ADMIN_SYSTEM') or has_role('ROLE_SUPER_ADMIN')")
 */
class AdminDevelopmentController extends Controller
{
    public function indexAction(Request $request): Response
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $prodUrl = $this->getParameter('cms_prod_url');

        $pagerfanta = new Pagerfanta(new SimpleDoctrineORMAdapter(
            $em->getRepository(Syslog::class)->getFindByQuery([], ['id' => 'DESC'])
        ));
        $pagerfanta->setMaxPerPage(20);

        try {
            $pagerfanta->setCurrentPage($request->query->get('page', 1));
        } catch (NotValidCurrentPageException $e) {
            throw $this->createNotFoundException();
        }

        return $this->render('@CMS/Admin/Development/index.html.twig', [
            'pagerfanta' => $pagerfanta,
            'prod_url'   => $prodUrl,
        ]);
    }

    public function syncAction(Request $request)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $lastSyslog = $em->getRepository('CMSBundle:Syslog')->findOneBy([], ['id' => 'DESC']);

        $prodUrl = $this->getParameter('cms_prod_url');

        // @todo получение ссылки без app_dev.php
        $url = $prodUrl.$this->generateUrl('cms_api_syslog');

        $allowKeys = $this->getParameter('cms_dev_api');

        if (isset($allowKeys[$this->getUser()->getUsername()])) {
            $key = $allowKeys[$this->getUser()->getUsername()];
        }

        $parameters = [
            'key' => $key,
        ];

        $output = $this->get('api_caller')->call(new HttpGetJson($url, $parameters, true));

        $message = '';
        $status  = false;

        if ($output) {
            $last_syslog_datetime = null;
            $last_syslog_id = null;

            if (isset($output['data']['last_syslog_id'])) {
                $last_syslog_id = $output['data']['last_syslog_id'];
            }

            if (isset($output['data']['last_syslog_datetime'])) {
                $last_syslog_datetime = $output['data']['last_syslog_datetime'];
            }

            if ($last_syslog_id) {
                if ($lastSyslog) {
                    if ($last_syslog_id == $lastSyslog->getId()) {
                        if ($lastSyslog->getCreatedAt()->format('Y-m-d H:i:s') == $last_syslog_datetime) {
                            $message = 'Структура синхронизована!';
                            $status  = true;
                        } else {
                            $message = 'На обоих стронах одинаковое кол-во записей в журнале, но даты не совпадают';
                        }
                    } elseif ($last_syslog_id > $lastSyslog->getId()) {
                        $message = 'На PROD кол-во записей в журнале больше, чем на LOCAL';
                    } else {
                        $message = 'На PROD кол-во записей в журнале меньше, чем на LOCAL';
                    }
                } else {
                    $message = 'На PROD записи в журнале есть, а на LOCAL нет';
                }
            } elseif ($last_syslog_id == $lastSyslog) {
                $message = 'На обоих стронах отсутствуют записи в журнале';
                $status  = true;
            } elseif ($lastSyslog) {
                $message = 'На LOCAL записи в журнале есть, а на PROD нет';
            }
        } else {
            $message = 'Ошибка подключения к PROD';
        }

        return $this->render('@CMS/Admin/Development/sync.html.twig', [
            'message'   => $message,
            'status'    => $status,
            'prod_url'  => $prodUrl,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function databaseAction(Request $request)
    {
        $prodUrl = $this->getParameter('cms_prod_url');

        if ($request->query->has('start')) {
            $url = $this->generateUrl('cms_api_get_database_dump');

            $allowKeys = $this->getParameter('cms_dev_api');
            if (isset($allowKeys[$this->getUser()->getUsername()])) {
                $key = $allowKeys[$this->getUser()->getUsername()];
            }

            $parameters = [
                'key' => $key,
            ];

            $settings = $this->get('settings');
            /** @var SettingModel $s */
            foreach ($settings->all() as $s) {
                $tmp = $settings->get($s->getBundle().':'.$s->getName());
            }

            $dumpFileGz = $this->getParameter('kernel.cache_dir').'/db_dump_'.date("Y-m-d_H-i-s").'.sql.gz';

            $client = new Client(['base_uri' => $prodUrl]);
            $client->request('GET', $url, [
                'query' => $parameters,
                'sink'  => $dumpFileGz,
            ]);

            $dumpFileSql = $this->ungzip($dumpFileGz);

            $HOST     = $this->getParameter('database_host');
            $DBUSER   = $this->getParameter('database_user');
            $DBPASSWD = $this->getParameter('database_password');
            $DATABASE = $this->getParameter('database_name');
            $PORT     = $this->getParameter('database_port');

            $local_backup = $this->getParameter('kernel.project_dir').'/var/db_dumps/mysql/'.date("Y-m-d_H-i-s").'_'.$DATABASE.'.sql.gz';
            passthru("mysqldump -u $DBUSER --password=$DBPASSWD $DATABASE | gzip --best > ".$local_backup);

            $application = new Application($this->get('kernel'));
            $application->setAutoExit(false);
            $input = new ArrayInput(['command' => 'doctrine:schema:update', '--force' => true, '--complete' => true]);
            $output = new BufferedOutput();

            $retval = $application->run($input, $output);

            $application = new Application($this->get('kernel'));
            $application->setAutoExit(false);
            $input = new ArrayInput(['command' => 'doctrine:schema:drop', '--force' => true]);
            $output = new BufferedOutput();

            $retval = $application->run($input, $output);

            if (empty($PORT)) {
                $PORT = 3306;
            }

            $mysql_import_cmd = 'mysql --host="'.$HOST.'" --port="'.$PORT.'" --user="'.$DBUSER.'" --password="'.$DBPASSWD.'" '.$DATABASE.' < '.$dumpFileSql;
            passthru($mysql_import_cmd);

            unlink($dumpFileGz);
            unlink($dumpFileSql);

            $this->addFlash('success', 'База данных синхронизирована.');

            return $this->redirectToRoute('cms_admin_development_db');
        }

        return $this->render('@CMS/Admin/Development/database.html.twig', [
            'prod_url'  => $prodUrl,
        ]);
    }

    protected function ungzip($file_name)
    {
        // Raising this value may increase performance
        $buffer_size = 4096; // read 4kb at a time
        $out_file_name = str_replace('.gz', '', $file_name);
        // Open our files (in binary mode)
        $file = gzopen($file_name, 'rb');
        $out_file = fopen($out_file_name, 'wb');
        // Keep repeating until the end of the input file
        while(!gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($out_file, gzread($file, $buffer_size));
        }
        // Files are done, close files
        fclose($out_file);
        gzclose($file);

        return $out_file_name;
    }
}
