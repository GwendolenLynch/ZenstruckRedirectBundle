<?php

namespace Zenstruck\RedirectBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Zenstruck\RedirectBundle\Model\NotFound;
use Zenstruck\RedirectBundle\Model\Redirect;
use Zenstruck\RedirectBundle\Tests\Fixture\Bundle\Entity\DummyRedirect;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class FunctionalTest extends WebTestCase
{
    /** @var Client */
    protected $client;

    /** @var EntityManager */
    protected $em;

    public function setUp()
    {
        $client = self::createClient();

        $application = new Application($client->getKernel());
        $application->setAutoExit(false);

        $application->run(new StringInput('doctrine:database:drop --force'), new NullOutput());
        $application->run(new StringInput('doctrine:database:create'), new NullOutput());
        $application->run(new StringInput('doctrine:schema:create'), new NullOutput());

        $this->em = $client->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->client = $client;

        $this->addTestData();
    }

    /**
     * @param string $source
     *
     * @return Redirect|null
     */
    protected function getRedirect($source)
    {
        if (null === $redirect = $this->em->getRepository('TestBundle:DummyRedirect')->findOneBy(array('source' => $source))) {
            return null;
        }

        $this->em->refresh($redirect);

        return $redirect;
    }

    /**
     * @return NotFound[]
     */
    protected function getNotFounds()
    {
        return $this->em->getRepository('TestBundle:DummyNotFound')->findAll();
    }

    protected function addTestData()
    {
        $this->em->createQuery('DELETE TestBundle:DummyRedirect')
            ->execute()
        ;

        $this->em->createQuery('DELETE TestBundle:DummyNotFound')
            ->execute()
        ;

        $this->em->persist(new DummyRedirect('/301-redirect', 'http://symfony.com'));
        $this->em->persist(new DummyRedirect('/302-redirect', 'http://example.com', false));

        $this->em->flush();
    }
}
