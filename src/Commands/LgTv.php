<?php

namespace Flexnst\LgTv\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

class LgTv extends Command
{
    protected $signature = 'lgtv {cmd} {param?}';

    protected $description = 'LG Smart TV commands';

    public function handle()
    {
        $command = $this->argument('cmd');
        $params = $this->argument('param');

        $fn = 'call_user_func';
        if(\Str::contains($params,',')){
            $fn = 'call_user_func_array';
            $params = explode(',', $params);
        }

        $device = \LgTv::device();
        $response = $fn([$device, $command], $params);
        if($this->output->getVerbosity() === OutputInterface::VERBOSITY_VERBOSE){
            dump($response);
        }
        return $response;
    }
}
