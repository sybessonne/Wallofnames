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

class UserCreateCommand extends Command
{
    protected static $defaultName = 'user:create';
   
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
        //$io->title(' User creation');

        $output->writeln([
            '',
            '<info> Enter an email (e.g. <comment>foo@bar.fr</comment>):</>',
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
            '<info> Enter a password: (length <comment>[10-30]</comment>)</>',
        ]);

        $passQuestion = new Question(' > ');
        $passQuestion->setHidden(true);
        $passQuestion->setHiddenFallback(false);
        $passQuestion->setValidator(function ($value) {
            if (trim($value) == '') {
                throw new \Exception('The password can not be empty');
            }
            if (strlen(trim($value)) < 10) {
                throw new \Exception('The password is too short');
            }
            if (strlen(trim($value)) > 30) {
                throw new \Exception('The password is too long');
            }
        
            return $value;
        });
    
        $password = $helper->ask($input, $output, $passQuestion);

        $output->writeln([
            '',
            '<info> Re-enter a password:</>',
        ]);

        $passQuestion = new Question(' > ');
        $passQuestion->setHidden(true);
        $passQuestion->setHiddenFallback(false);    
        $rePassword = $helper->ask($input, $output, $passQuestion);

        if(trim($password) !== trim($rePassword))
        {
            throw new \Exception("Password are not the same");
        }

        $output->writeln([
            '',
            '<info> Enter a role: [<comment>ROLE_USER</comment>]</>',
        ]);

        $roles = ['ROLE_ADMIN'];
        $roleQuestion = new Question(' > ', 'ROLE_USER');
        $roleQuestion->setAutocompleterValues($roles);
        $roleQuestion->setValidator(function ($value) {
            $roles = ['ROLE_ADMIN', 'ROLE_USER'];
            if (!in_array(trim($value), $roles)) {
                throw new \Exception('The Role is incorrect');
            }
        
            return $value;
        });
    
        $role = $helper->ask($input, $output, $roleQuestion);


        $user = new User();
        $user->setEmail(trim($email));
        $user->setRoles(array(trim($role)));
        $user->setPassword($this->passwordEncoder->encodePassword(
                         $user,
                         trim($password)
                     ));

        $this->em->persist($user);
        $this->em->flush();
        
        $io->success('User created successfully');

        return 0;
    }
}
