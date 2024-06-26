<?php

declare(strict_types=1);

namespace BeyondCode\SelfDiagnosis\Checks;

use Laravel\Horizon\Contracts\MasterSupervisorRepository;

use function collect;
use function trans;

class HorizonIsRunning implements Check
{
    private MasterSupervisorRepository $supervisorRepository;

    public function __construct(MasterSupervisorRepository $supervisorRepository)
    {
        $this->supervisorRepository = $supervisorRepository;
    }

    public function name(array $config): string
    {
        return trans('self-diagnosis::checks.horizon_is_running.name');
    }

    public function check(array $config): bool
    {
        $masters = $this->supervisorRepository->all();
        if (! $masters) {
            return false;
        }

        return ! collect($masters)->contains(static function ($master) {
            return $master->status === 'paused';
        });
    }

    public function message(array $config): string
    {
        return trans('self-diagnosis::checks.horizon_is_running.message');
    }
}
