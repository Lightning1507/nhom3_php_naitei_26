<?php

namespace App\Console\Commands;

use App\Models\ServiceType;
use App\Support\ServiceSchema;
use Illuminate\Console\Command;

class NormalizeServiceDocumentRequirements extends Command
{
    protected $signature = 'service:normalize-document-requirements';

    protected $description = 'Normalize document_requirements of all service types into the canonical {code, label, required, type} shape';

    public function handle(): int
    {
        $updated = 0;

        foreach (ServiceType::all() as $serviceType) {
            $requirements = ServiceSchema::normalizeDocumentRequirements($serviceType->document_requirements);

            if ($requirements !== $serviceType->document_requirements) {
                $serviceType->document_requirements = $requirements;
                $serviceType->save();

                $updated++;
            }
        }

        $this->info(sprintf('Normalized document requirements for %d service type(s).', $updated));

        return self::SUCCESS;
    }
}
