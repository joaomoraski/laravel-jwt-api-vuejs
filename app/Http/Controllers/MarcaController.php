<?php

namespace App\Http\Controllers;

use App\Http\Requests\MarcaCreateUpdateRequest;
use App\Models\Marca;
use App\Repositories\MarcaRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class MarcaController extends Controller
{

    public function __construct(Marca $marca)
    {
        $this->marca = $marca;
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $marcaRepository = new MarcaRepository($this->marca);

        if($request->has('atributos_modelos')) { // verifica se existe o parametro de atributos_marca
            $atributos_modelos = 'modelos:id,'.$request->atributos_modelos; // se existe atribui a variavel
            // pega os modelos usando o with para pegar o id da marca e os atributos passados
            $marcaRepository->selectAtributosRegistrosRelacionados($atributos_modelos);
        } else {
            // se nao so pega tudo com todas as informacoes da marca
            $marcaRepository->selectAtributosRegistrosRelacionados('modelos');
        }

        if($request->has('filtro')) {
            $marcaRepository->selectWithFilter($request->filtro);
        }

        if($request->has('atributos')) {
            // se tem oparametro atributos ele explode em , e seleciona apenas aqueles atributos
            $atributos = explode(',', $request->atributos);
            $marcaRepository->selectFields($atributos);
        }

        return response()->json($marcaRepository->getResult(), 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate($this->marca->rules(), $this->marca->feedback());
//        $validated = $request->validated();

        $imagem = $request->file('imagem');
        $imagem_urn = $imagem->store('imagens', 'public');
        $marca = $this->marca->create([
            'nome' => $request->nome,
            'imagem' => $imagem_urn
        ]);
        return response()->json($marca, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $marca = $this->marca->with('modelos')->find($id);
        if ($marca === null){
            return response()->json(['erro' => 'Recurso pesquisado nao existe'], 404);
        }
        return response()->json($marca, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Integer
     * @return JsonResponse
     */
    public function update(MarcaCreateUpdateRequest $request, $id): JsonResponse
    {
        $marca = $this->marca->find($id);
        if ($marca === null) return response()->json(['erro' => 'Recurso pesquisado nao existe'], 404);

        $validated = $request->validated();

        if ($request->file('imagem')){
            Storage::disk('public')->delete($marca->imagem);
        }

        $imagem = $request->file('imagem');
        $imagem_urn = $imagem->store('imagens', 'public');

        $marca->fill($request->all());
        $marca->imagem = $imagem_urn;
        $marca->save();
//        $marca->update([
//            'nome' => $request->nome,
//            'imagem' => $imagem_urn
//        ]);
//        return response()->json(['data' => $marca, 'message' => 'Atualização realizada sem problemas.'], 200);
        return response()->json($marca, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Integer
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $marca = $this->marca->find($id);
        if ($marca === null){
            return response()->json(['erro' => 'Recurso pesquisado nao existe'], 404);
        }

        Storage::disk('public')->delete($marca->imagem);
        $marca->delete();
        return response()->json(['msg' => 'A marca foi removida com sucesso!'], 200);
    }
}
