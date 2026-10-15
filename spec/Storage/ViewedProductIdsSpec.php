<?php

declare(strict_types=1);

namespace spec\Vldmrk\SyliusViewedProductsPlugin\Storage;

use PhpSpec\ObjectBehavior;

final class ViewedProductIdsSpec extends ObjectBehavior
{
    function it_keeps_positive_integer_ids_without_repeats(): void
    {
        $this->normalize(['3', 1, '3', 'x', '0', -2, 2.5, [], null, '7'], 10)->shouldReturn([3, 1, 7]);
    }

    function it_cuts_the_list_to_the_limit(): void
    {
        $this->normalize([1, 2, 3, 4], 2)->shouldReturn([1, 2]);
    }

    function it_moves_a_viewed_id_to_the_front(): void
    {
        $this->prepend([1, 2, 3], 3, 10)->shouldReturn([3, 1, 2]);
        $this->prepend([1, 2, 3], 4, 3)->shouldReturn([4, 1, 2]);
    }

    function it_ignores_a_product_without_an_id(): void
    {
        $this->prepend([1, 2], 0, 10)->shouldReturn([1, 2]);
    }
}
