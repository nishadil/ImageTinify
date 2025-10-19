<?php

namespace Nishadil\ImageTinify\Commands;

use Nishadil\ImageTinify\ImageTinify;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TinifyCommand extends Command
{
    protected static $defaultName = 'imagetinify';

    protected function configure(): void
    {
        $this
            ->setDescription('Compress an image file with ImageTinify')
            ->addArgument('input', InputArgument::REQUIRED, 'Input image path')
            ->addArgument('output', InputArgument::OPTIONAL, 'Output image path')
            ->addOption('quality', null, InputOption::VALUE_OPTIONAL, 'Quality integer (JPEG/WebP) or range (PNG)', '')
            ->addOption('mode', null, InputOption::VALUE_OPTIONAL, 'Mode: lossy or lossless', 'lossy');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $in = $input->getArgument('input');
        $out = $input->getArgument('output') ?? null;
        $quality = $input->getOption('quality') ?: null;
        $mode = $input->getOption('mode') ?: 'lossy';

        $tin = new ImageTinify();
        try {
            $ok = $tin->compress($in, $out, [
                'quality' => $quality,
                'mode' => $mode
            ]);
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        if ($ok) {
            $output->writeln('<info>Image compressed successfully.</info>');
            return Command::SUCCESS;
        }

        $output->writeln('<comment>No output produced.</comment>');
        return Command::FAILURE;
    }
}
