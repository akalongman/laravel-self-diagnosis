<?php

declare(strict_types=1);

namespace BeyondCode\SelfDiagnosis;

use BeyondCode\SelfDiagnosis\Checks\Check;
use Illuminate\Console\Command;

use function array_key_exists;
use function count;
use function is_numeric;

use const PHP_EOL;

class SelfDiagnosisCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'self-diagnosis {environment?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform application self diagnosis.';

    private array $messages = [];

    public function handle(): int
    {
        $this->runChecks(config('self-diagnosis.checks', []), trans('self-diagnosis::commands.self_diagnosis.common_checks'));

        $environment = $this->argument('environment') ?: app()->environment();
        $environmentChecks = config('self-diagnosis.environment_checks.' . $environment, []);

        if (empty($environmentChecks) && array_key_exists($environment, config('self-diagnosis.environment_aliases'))) {
            $environment = config('self-diagnosis.environment_aliases.' . $environment);
            $environmentChecks = config('self-diagnosis.environment_checks.' . $environment, []);
        }

        $this->runChecks($environmentChecks, trans('self-diagnosis::commands.self_diagnosis.environment_specific_checks', ['environment' => $environment]));

        if (count($this->messages)) {
            $this->error(trans('self-diagnosis::commands.self_diagnosis.failed_checks'));

            foreach ($this->messages as $message) {
                $this->output->writeln('<fg=red>' . $message . '</fg=red>');
                $this->output->writeln('');
            }

            return 1; // Any other return code then 0 means exit with error
        }
        $this->info(trans('self-diagnosis::commands.self_diagnosis.success'));

        return 0;
    }

    protected function runChecks(array $checks, string $title): void
    {
        $max = count($checks);
        $current = 1;

        $this->output->writeln('|-------------------------------------');
        $this->output->writeln('| ' . $title);
        $this->output->writeln('|-------------------------------------');

        foreach ($checks as $check => $config) {
            if (is_numeric($check)) {
                $check = $config;
                $config = [];
            }

            $checkClass = app($check);

            $this->output->write(trans('self-diagnosis::commands.self_diagnosis.running_check', [
                'current' => $current,
                'max'     => $max,
                'name'    => $checkClass->name($config),
            ]));

            $this->runCheck($checkClass, $config);

            $current++;
        }

        $this->output->writeln('');
    }

    protected function runCheck(Check $check, array $config): void
    {
        if ($check->check($config)) {
            $this->output->write('<fg=green>✔</fg=green>');
        } else {
            $this->output->write('<fg=red>✘</fg=red>');

            $this->messages[] = $check->message($config);
        }

        $this->output->write(PHP_EOL);
    }
}
