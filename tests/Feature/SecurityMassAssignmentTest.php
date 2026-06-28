<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // VULN #3: MEDIUM — is_admin no fillable [CORRIGIDO]
    // =========================================================================

    /** @test */
    public function is_admin_nao_esta_no_fillable(): void
    {
        $fillable = (new User())->getFillable();

        $this->assertNotContains('is_admin', $fillable,
            'is_admin não deve estar no fillable — risco de elevação de privilégio.'
        );
    }

    /** @test */
    public function mass_assignment_com_is_admin_e_ignorado(): void
    {
        // Tenta criar usuário admin via mass assignment
        $attacker = User::create([
            'name' => 'Hacker',
            'email' => 'hacker@evil.com',
            'password' => '12345678',
            'is_admin' => true,  // <-- deve ser ignorado pelo guarded
        ]);

        // O campo is_admin NÃO deve ser alterado via mass assignment
        $this->assertFalse($attacker->isAdmin(),
            'is_admin=true passado em mass assignment deve ser IGNORADO.'
        );

        $this->assertFalse($attacker->fresh()->isAdmin(),
            'Após refresh do banco, usuário NÃO deve ser admin.'
        );
    }

    /** @test */
    public function admin_criado_corretamente_via_atribuicao_explicita(): void
    {
        // A forma correta de criar admin
        $admin = new User([
            'name' => 'Admin Legítimo',
            'email' => 'admin@legitimo.com',
            'password' => '12345678',
        ]);
        $admin->is_admin = true;  // atribuição explícita (não mass assignment)
        $admin->save();

        $this->assertTrue($admin->isAdmin(),
            'Admin criado corretamente via atribuição explícita de propriedade.'
        );
    }
}
