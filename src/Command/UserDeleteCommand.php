<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserDeleteCommand extends Command
{
    protected static $defaultName = 'user:delete';
   
    private $em;
    private $passwordEncoder;

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->em = $em;
        $this->passwordEncoder = $passwordEncoder;
        parent::__construct();
    }


    protected function configure()
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        //$io->title(' User deletion');

        $output->writeln([
            '',
            '<info> Enter the user email (e.g. <comment>foo@bar.fr</comment>):</>',
        ]);

        $helper = $this->getHelper('question');

        $emailQuestion = new Question(' >  ', '');
        $emailQuestion->setValidator(function ($value) {
            if (!filter_var(trim($value), FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('The Email is not correct');
            }
        
            return $value;
        });
        $email = $helper->ask($input, $output, $emailQuestion);

        $output->writeln([
            '',
            '<info> Enter the user password</>',
        ]);

        $passQuestion = new Question(' > ');
        $passQuestion->setHidden(true);
        $passQuestion->setHiddenFallback(false);
        $passQuestion->setValidator(function ($value) {
            if (trim($value) == '') {
                throw new \Exception('The password can not be empty');
            }
        
            return $value;
        });
    
        $password = $helper->ask($input, $output, $passQuestion);

        $user = $this->em
        ->getRepository(User::class)
        ->findOneByEmail($email);

        if (!$user) {
            throw new \Exception('User not found');
        }
        else
        {
            if($this->passwordEncoder->isPasswordValid($user, $password))
            {
                $this->em->remove($user);
                $this->em->flush();
                $io->success('User deleted successfully');
            }
            else
            {
                throw new \Exception('Password incorrect');
            }
        }

        return 0;
    }
}
