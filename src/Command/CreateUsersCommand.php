<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class CreateUsersCommand extends Command
{
    protected static $defaultName = 'app:create-users';

    private $entityManager;
    private $passwordEncoder;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Creates default users')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email for the user')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password for the user');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $emails = ['admin1@example.com', 'admin2@example.com'];
        $password = 'admin11';

        foreach ($emails as $email) {
            $user = new User();
            $user->setEmail($email);
            $user->setUsername(substr($email, 0, strpos($email, '@')));
            $user->setPhone('1234567890');
            $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
            $user->setRoles(['ROLE_ADMIN']);

            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();

        $output->writeln('Users created successfully!');
        return 0;
       // return Command::SUCCESS;
    }
}
