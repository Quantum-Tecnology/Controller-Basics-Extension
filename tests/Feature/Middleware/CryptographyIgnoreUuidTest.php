<?php

declare(strict_types = 1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use QuantumTecnology\ControllerBasicsExtension\Middleware\CryptographyMiddleware;
use Vinkla\Hashids\Facades\Hashids;

/*
|--------------------------------------------------------------------------
| hashids.ignore_uuid
|--------------------------------------------------------------------------
|
| O `regex` escolhe os campos pelo NOME; um projeto que mistura chave inteira
| (exposta como hashid) com tabela de chave UUID recebe o UUID em `{id}` ou
| `*_id`, e o decoder tentava decodificá-lo: 404 na rota, 400 no input — e só
| com a criptografia ligada, ou seja, só em produção.
|
*/

const UUID = '0199a3f2-7c1e-7b3a-9d4e-1f2a3b4c5d6e';

beforeEach(function (): void {
    // O Testbench não faz o auto-discovery dos providers do projeto.
    $this->app->register(Vinkla\Hashids\HashidsServiceProvider::class);

    config([
        'hashids.enable_cryptography' => true,
        'hashids.ignore_uuid'         => false,
        'hashids.default'             => 'main',
        'hashids.connections.main'    => [
            'salt'     => 'teste',
            'length'   => 10,
            'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        ],
    ]);

    // Devolve o que o controller recebeu, já decodificado. ⚠️ Em chaves que NÃO
    // casam com o regex: `coupon_id` na resposta seria codificado de novo pelo
    // Encoder, e o teste veria o hash em vez do que o controller recebeu.
    Route::middleware(CryptographyMiddleware::class)->get('/crypt/{id}', fn (Request $request, $id) => response()->json([
        'recebido' => $id,
        'cupom'    => $request->input('coupon_id'),
        'lista'    => $request->input('coupon_ids'),
    ]));

    // Devolve ids como o banco os daria, para conferir a codificação.
    Route::middleware(CryptographyMiddleware::class)->get('/crypt-resposta', fn () => response()->json([
        'id'      => 42,
        'uuid_pk' => ['id' => UUID],
    ]));
});

function hashid(int $id): string
{
    return Hashids::encode($id);
}

it('hashid continua sendo decodificado, com a opção ligada ou desligada', function (bool $ignorar): void {
    config(['hashids.ignore_uuid' => $ignorar]);

    $this->getJson('/crypt/' . hashid(7) . '?coupon_id=' . hashid(9))
        ->assertOk()
        ->assertJsonPath('recebido', 7)
        ->assertJsonPath('cupom', 9);
})->with([
    'ignore_uuid desligado' => false,
    'ignore_uuid ligado'    => true,
]);

it('desligado (padrão): UUID na rota continua dando 404, como antes', function (): void {
    $this->getJson('/crypt/' . UUID)->assertNotFound();
});

it('desligado (padrão): UUID num campo *_id continua dando 400, como antes', function (): void {
    $this->getJson('/crypt/' . hashid(1) . '?coupon_id=' . UUID)->assertStatus(400);
});

it('ligado: UUID na rota passa intacto', function (): void {
    config(['hashids.ignore_uuid' => true]);

    $this->getJson('/crypt/' . UUID)
        ->assertOk()
        ->assertJsonPath('recebido', UUID);
});

it('ligado: UUID num campo *_id passa intacto', function (): void {
    config(['hashids.ignore_uuid' => true]);

    $this->getJson('/crypt/' . hashid(1) . '?coupon_id=' . UUID)
        ->assertOk()
        ->assertJsonPath('cupom', UUID);
});

it('ligado: lista mista de hashid e UUID decodifica só o hashid', function (): void {
    config(['hashids.ignore_uuid' => true]);

    $this->getJson('/crypt/' . hashid(1) . '?coupon_ids[]=' . hashid(5) . '&coupon_ids[]=' . UUID)
        ->assertOk()
        ->assertJsonPath('lista', [5, UUID]);
});

it('ligado: lixo continua sendo recusado', function (): void {
    config(['hashids.ignore_uuid' => true]);

    // Nem hashid, nem UUID: a opção não pode virar porta aberta.
    $this->getJson('/crypt/' . hashid(1) . '?coupon_id=nao-e-uuid-nem-hash')->assertStatus(400);
    $this->getJson('/crypt/0199a3f2-nao-e-uuid')->assertNotFound();
});

it('a resposta codifica o inteiro e deixa o UUID intacto', function (): void {
    $this->getJson('/crypt-resposta')
        ->assertOk()
        ->assertJsonPath('id', hashid(42))
        ->assertJsonPath('uuid_pk.id', UUID);
});

it('entrada e saída usam a mesma regra de id (Identifier)', function (): void {
    // Campo que só é id por estar em `hashids.attributes`: se a regra estivesse
    // duplicada e divergisse, um lado o trataria como id e o outro não.
    config(['hashids.attributes' => ['codigo'], 'hashids.ignore_uuid' => true]);

    Route::middleware(CryptographyMiddleware::class)->get('/crypt-simetria', fn (Request $request) => response()->json([
        'recebido' => $request->input('codigo'),
        'codigo'   => 77,
        'outro'    => ['codigo' => UUID],
    ]));

    $this->getJson('/crypt-simetria?codigo=' . hashid(77))
        ->assertOk()
        ->assertJsonPath('recebido', 77)
        ->assertJsonPath('codigo', hashid(77))
        ->assertJsonPath('outro.codigo', UUID);
});
