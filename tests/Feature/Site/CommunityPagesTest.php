<?php

namespace Tests\Feature\Site;

use Tests\TestCase;

class CommunityPagesTest extends TestCase
{
    /** @test */
    public function test_it_loads_the_vt_guide()
    {
        $this->get(route('site.community.vt-guide'))->assertOk();
    }

    /** @test */
    public function test_it_load_the_terms()
    {
        $this->get(route('site.community.terms'))->assertOk();
    }

    /** @test */
    public function test_it_loads_team_speak()
    {
        $this->get(route('site.community.teamspeak'))->assertOk();
    }
}
