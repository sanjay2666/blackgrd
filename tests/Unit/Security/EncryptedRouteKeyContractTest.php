<?php

namespace Tests\Unit\Security;

use App\Models\Individual;
use App\Models\IndividualAddress;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class EncryptedRouteKeyContractTest extends TestCase
{
    public function test_routable_models_emit_encrypted_keys_that_the_binding_trait_decodes(): void
    {
        $user = new User();
        $user->setRawAttributes(['id' => 14]);

        foreach ([
            new Individual(['id' => 11]),
            new IndividualAddress(['ind_add_id' => 12]),
            new Role(['id' => 13]),
            $user,
        ] as $model) {
            $this->assertFalse(ctype_digit((string) $model->getRouteKey()));
            $this->assertSame((string) $model->getKey(), dec($model->getRouteKey()));
        }
    }
}
