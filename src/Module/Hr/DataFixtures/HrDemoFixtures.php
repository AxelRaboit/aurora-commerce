<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\DataFixtures;

use Aurora\Core\DataFixtures\CoreDemoFixtures;
use Aurora\Module\Hr\Employee\Entity\Employee;
use Aurora\Module\Platform\Agency\Entity\Agency;
use Aurora\Module\Platform\Service\Entity\Service;
use Aurora\Module\Platform\User\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

use function assert;

/**
 * Demo HR data: 14 employees linked to the demo users, agencies and services
 * seeded by {@see CoreDemoFixtures}. Dev/test only.
 */
class HrDemoFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function getDependencies(): array
    {
        return [CoreDemoFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        assert($manager instanceof EntityManagerInterface);

        $users = [];
        for ($i = 0; $i < CoreDemoFixtures::USER_COUNT; ++$i) {
            $users[] = $this->getReference(CoreDemoFixtures::userRef($i), User::class);
        }

        $agencies = $manager->getRepository(Agency::class)->findAll();
        $services = $manager->getRepository(Service::class)->findAll();

        $defs = [
            ['firstName' => 'Sophie',    'lastName' => 'Martin',    'title' => 'Responsable RH',              'email' => 'sophie.martin@aurora-tech.fr',    'phone' => '+33 6 10 11 12 13', 'hired' => '2019-03-01', 'left' => null,         'svc' => 'Ressources Humaines', 'agc' => 'Siège Social'],
            ['firstName' => 'Thomas',    'lastName' => 'Dubois',    'title' => 'Développeur Senior PHP',       'email' => 'thomas.dubois@aurora-tech.fr',    'phone' => '+33 6 20 21 22 23', 'hired' => '2020-06-15', 'left' => null,         'svc' => 'Développement',       'agc' => 'Agence Nord'],
            ['firstName' => 'Clara',     'lastName' => 'Lefèvre',   'title' => 'Chef de projet technique',     'email' => 'clara.lefevre@aurora-tech.fr',    'phone' => '+33 6 30 31 32 33', 'hired' => '2018-09-01', 'left' => null,         'svc' => 'Développement',       'agc' => 'Agence Nord'],
            ['firstName' => 'Julien',    'lastName' => 'Moreau',    'title' => 'Commercial Grands Comptes',    'email' => 'julien.moreau@aurora-tech.fr',    'phone' => '+33 6 40 41 42 43', 'hired' => '2021-01-10', 'left' => null,         'svc' => 'Commercial',          'agc' => 'Agence Sud'],
            ['firstName' => 'Camille',   'lastName' => 'Bernard',   'title' => 'Chargée de communication',    'email' => 'camille.bernard@aurora-tech.fr',  'phone' => '+33 6 50 51 52 53', 'hired' => '2022-04-04', 'left' => null,         'svc' => 'Marketing',           'agc' => 'Siège Social'],
            ['firstName' => 'Antoine',   'lastName' => 'Petit',     'title' => 'Développeur Full-Stack',       'email' => 'antoine.petit@aurora-tech.fr',    'phone' => '+33 6 60 61 62 63', 'hired' => '2021-09-20', 'left' => null,         'svc' => 'Développement',       'agc' => 'Agence Est'],
            ['firstName' => 'Lucie',     'lastName' => 'Girard',    'title' => 'Assistante de direction',      'email' => 'lucie.girard@aurora-tech.fr',     'phone' => '+33 6 70 71 72 73', 'hired' => '2017-11-15', 'left' => null,         'svc' => 'Direction',           'agc' => 'Siège Social'],
            ['firstName' => 'Marc',      'lastName' => 'Rousseau',  'title' => 'Ingénieur DevOps',             'email' => 'marc.rousseau@aurora-tech.fr',    'phone' => '+33 6 80 81 82 83', 'hired' => '2020-02-01', 'left' => null,         'svc' => 'Développement',       'agc' => 'Agence Ouest'],
            ['firstName' => 'Emma',      'lastName' => 'Dupont',    'title' => 'Designer UX/UI',               'email' => 'emma.dupont@aurora-tech.fr',      'phone' => '+33 6 90 91 92 93', 'hired' => '2022-07-18', 'left' => null,         'svc' => 'Marketing',           'agc' => 'Siège Social'],
            ['firstName' => 'Nicolas',   'lastName' => 'Lambert',   'title' => 'Responsable Commercial',       'email' => 'nicolas.lambert@aurora-tech.fr',  'phone' => '+33 7 00 01 02 03', 'hired' => '2016-05-09', 'left' => null,         'svc' => 'Commercial',          'agc' => 'Agence Sud'],
            ['firstName' => 'Inès',      'lastName' => 'Fontaine',  'title' => 'Développeuse Backend',         'email' => 'ines.fontaine@aurora-tech.fr',    'phone' => '+33 7 10 11 12 13', 'hired' => '2023-01-09', 'left' => null,         'svc' => 'Développement',       'agc' => 'Agence Nord'],
            ['firstName' => 'Romain',    'lastName' => 'Garnier',   'title' => 'Consultant technique',         'email' => 'romain.garnier@aurora-tech.fr',   'phone' => '+33 7 20 21 22 23', 'hired' => '2019-08-26', 'left' => '2024-06-30', 'svc' => 'Développement',       'agc' => 'Agence Est'],
            ['firstName' => 'Mathilde',  'lastName' => 'Chevalier', 'title' => 'Chargée de recrutement',       'email' => 'mathilde.chevalier@aurora-tech.fr', 'phone' => '+33 7 30 31 32 33', 'hired' => '2023-09-04', 'left' => null,         'svc' => 'Ressources Humaines', 'agc' => 'Siège Social'],
            ['firstName' => 'Pierre',    'lastName' => 'Morel',     'title' => 'Directeur Technique',          'email' => 'pierre.morel@aurora-tech.fr',     'phone' => '+33 7 40 41 42 43', 'hired' => '2015-01-05', 'left' => null,         'svc' => 'Direction',           'agc' => 'Siège Social'],
            ['firstName' => 'Laura',     'lastName' => 'Simon',     'title' => 'Développeuse Mobile',          'email' => 'laura.simon@aurora-tech.fr',      'phone' => '+33 7 50 51 52 53', 'hired' => '2024-03-11', 'left' => null,         'svc' => 'Développement',       'agc' => 'Agence Ouest'],
        ];

        $agencyMap = [];
        foreach ($agencies as $a) {
            $agencyMap[$a->getName()] = $a;
        }

        $serviceMap = [];
        foreach ($services as $s) {
            $serviceMap[$s->getName()] = $s;
        }

        foreach ($defs as $i => $def) {
            $employee = new Employee();
            $employee->setFirstName($def['firstName'])
                     ->setLastName($def['lastName'])
                     ->setJobTitle($def['title'])
                     ->setWorkEmail($def['email'])
                     ->setPhone($def['phone'])
                     ->setHiredAt(new DateTimeImmutable($def['hired']))
                     ->setLeftAt(null !== $def['left'] ? new DateTimeImmutable($def['left']) : null)
                     ->setUser($users[$i] ?? null)
                     ->setService($serviceMap[$def['svc']] ?? null)
                     ->setAgency($agencyMap[$def['agc']] ?? null);
            $manager->persist($employee);
        }

        $manager->flush();
    }
}
