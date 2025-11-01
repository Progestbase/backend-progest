<?php

namespace App\Models;

/**
 * Classe de compatibilidade para código legado que ainda usa Polo.
 * Ela estende Unidade para apontar para a nova implementação.
 */
class Polo extends Unidade
{
    // wrapper vazio — mantém compatibilidade com chamadas antigas como Polo::query(), Polo::factory(), etc.
}
