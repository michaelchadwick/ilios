<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists all users with root-level privileges.
 *
 * Class ListRootUsersCommand
 */
class ListRootUsersCommand extends Command
{
    /**
     * @var string
     */
    public const COMMAND_NAME = 'ilios:list-root-users';

    /**
     * @var UserRepository
     */
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setAliases(['ilios:maintenance:list-root-users'])
            ->setDescription('Lists all users with root-level privileges.');
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $users = $this->userRepository->findDTOsBy(['root' => true]);

        if (empty($users)) {
            $output->writeln("No users with root-level privileges found.");
            return 0;
        }

        $rows = array_map(function ($dto) {
            return [
                $dto->id,
                $dto->firstName,
                $dto->lastName,
                $dto->email,
                $dto->phone,
                ($dto->enabled ? 'Yes' : 'No')
            ];
        }, $users);

        $table = new Table($output);
        $table
            ->setHeaders(['Id', 'First', 'Last', 'Email', 'Phone Number', 'Enabled'])
            ->setRows($rows)
        ;
        $table->render();

        return 0;
    }
}
