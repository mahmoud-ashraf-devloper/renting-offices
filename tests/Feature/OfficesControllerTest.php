<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OfficesControllerTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @test
     */
    public function itListsOfficesWithPagination()
    {
        Office::factory(3)->create();
        $response = $this->get('/api/offices');
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure(['data'=> 'meta','links']);
        $response->assertOk();
    }

    /**
     * @test
     */

    public function itListsOnlyApprovedAndVisibleOffices()
    {
        Office::factory(3)->create();
        Office::factory()->create(['hidden' => true]);
        Office::factory()->create(['approval_status' => Office::APPROVAL_PENDING]);

        $response = $this->get('/api/offices');

        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure(['data'=> 'meta','links']);
        $response->assertOk();
    }

    /**
     * @test
     */

    public function itFiltersByUserIdAsHost()
    {
        Office::factory(3)->create();
        $host = User::factory()->create();

        Office::factory()->for($host)->create();
        $response = $this->get(
            '/api/offices?host='.$host->id
        );

        $response->assertJsonCount(1, 'data');
        $response->assertJsonStructure(['data'=> 'meta','links']);
        $this->assertEquals($host->id, $response->json('data')[0]['id']);
        $response->assertOk();
    }

    /**
     * @test
     */

    public function itReturnsReservedOfficesForUser()
    {
        Office::factory(3)->create();
        $user = User::factory()
                    ->create();

        $office = Office::factory()
                        ->for($user)
                        ->create();

        // this does not match
        Reservation::factory()
                    ->for($office)
                    ->create();

        $reservation = Reservation::factory()
                            ->for($user)
                            ->for($office)
                            ->create();

        $response = $this->get(
            '/api/offices?user='.$user->id
        );

        $response->assertJsonCount(1, 'data');
        $response->assertJsonStructure(['data'=> 'meta','links']);
        $this->assertEquals($user->id, $response->json('data')[0]['id']);
        $response->assertOk();
    }

    /**
     * @test
     */

    public function itHasImagesTagsAndUsers()
    {
        $user = User::factory()->create();
        $office  = Office::factory()->for($user)->create();
        $tag = Tag::factory()->create();
        $office->tags()->attach($tag);
        $image  = $office->images()->create(['path' => 'image.png']);

        $response  = $this->get('/api/offices');

        $response->assertJsonStructure(
            [
                'data' => [
                    0 => [
                        'images',
                        'user',
                        'tags'
                    ]
                ]
            ]
        );

        $response->assertOk();
    }

    /**
     * @test
     */

    public function itReturnsCountOfActiveReservations()
    {
        $office = Office::factory()->create();

        Reservation::factory()
                    ->for($office)
                    ->create(['status' => Reservation::STATUS_ACTIVE]);
        Reservation::factory()
                    ->for($office)
                    ->create(['status' => Reservation::STATUS_CANCELLED]);

        $response = $this->get('/api/offices');

        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'reservations_count'
                ]
            ]
        ]);
        $this->assertEquals(1,$response->json()['data'][0]['reservations_count']);
        $response->assertOk();
    }

    /**
     * @test
     */

    public function itReturnsNearestOffice()
    {

        $office2 = Office::factory()->create([
            'lat' => '26.66768281332212',
            'lng' => '31.66211745200362',
            'title' => 'Sohag'
        ]);

        $office1 = Office::factory()->create([
            'lat' => '28.383651311619527',
            'lng' => '30.76389819563454',
            'title' => 'Samalot'
        ]);



        $office3 = Office::factory()->create([
            'lat' => '25.299415418970888',
            'lng' => '32.55639499853463',
            'title' => 'Aswan'
        ]);


        $response = $this->get('/api/offices?lat=28.19016339957055&lng=30.686993894770463');

        $this->assertEquals($office1->id, $response->json()['data'][0]['id']);
        $this->assertEquals($office2->id, $response->json()['data'][1]['id']);
        $this->assertEquals($office3->id, $response->json()['data'][2]['id']);
        $response->assertOk();

        $response = $this->get('/api/offices');

        $this->assertEquals($office2->id, $response->json()['data'][0]['id']);
        $this->assertEquals($office1->id, $response->json()['data'][1]['id']);
        $this->assertEquals($office3->id, $response->json()['data'][2]['id']);
        $response->assertOk();
    }

}
