<?php

namespace App\Http\Controllers\Candidato;

use App\Http\Controllers\Controller;
use App\Models\Candidato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Tela de login da área do candidato.
     * Apenas renderiza a view.
     */
    public function showLoginForm()
    {
        return view('site.candidato.auth.login');
    }

    /**
     * Login definitivo (CPF + senha).
     */
    public function login(Request $request)
    {
        // Validação server-side
        $data = $request->validate(
            [
                'cpf'      => ['required'],
                'password' => ['required'],
                'remember' => ['nullable'],
            ],
            [
                'cpf.required'      => 'Informe seu CPF.',
                'password.required' => 'Informe sua senha.',
            ]
        );

        // Normaliza CPF para apenas dígitos
        $cpfDigits = preg_replace('/\D+/', '', $data['cpf'] ?? '');

        $user = Candidato::query()
            ->whereRaw("REPLACE(REPLACE(REPLACE(cpf,'.',''),'-',''),'/','') = ?", [$cpfDigits])
            ->first();

        if (!$user) {
            return back()
                ->withErrors(['cpf' => 'CPF não encontrado.'])
                ->withInput(['cpf' => $request->input('cpf')]);
        }

        if (isset($user->status) && !$user->status) {
            return back()
                ->withErrors(['cpf' => 'Seu acesso está inativo.'])
                ->withInput(['cpf' => $request->input('cpf')]);
        }

        if (!Hash::check($data['password'], $user->password)) {
            return back()
                ->withErrors(['password' => 'Senha inválida.'])
                ->withInput(['cpf' => $request->input('cpf')]);
        }

        // Autentica no guard "candidato"
        Auth::guard('candidato')->login($user, (bool)$request->boolean('remember', false));

        // Atualiza último login (se existir a coluna)
        if ($user->isFillable('last_login_at') || \Schema::hasColumn($user->getTable(), 'last_login_at')) {
            $user->forceFill(['last_login_at' => now()])->save();
        }

        // 👉 Sempre manda para a home do candidato (não usa mais "intended")
        return redirect()->route('candidato.home');
    }

    /**
     * Logout da área do candidato.
     */
    public function logout(Request $request)
    {
        Auth::guard('candidato')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('candidato.login')
            ->with('status', 'Você saiu da sua conta.');
    }

    /* =======================================================
     * CADASTRO DE CANDIDATO
     * ======================================================= */

    public function showRegisterForm(Request $request)
    {
        // Permite pré-preencher o CPF vindo da checagem (?cpf=...)
        $prefillCpf = $request->query('cpf');

        return view('site.candidato.auth.register', [
            'prefillCpf' => $prefillCpf,
        ]);
    }

    public function register(Request $request)
    {
        // Normaliza CPF (apenas dígitos)
        $cpf = preg_replace('/\D+/', '', (string) $request->input('cpf'));

        $validated = $request->validate(
            [
                'nome'                  => ['required', 'string', 'max:120'],
                'email'                 => ['required', 'email', 'max:160', 'unique:candidatos,email'],
                'cpf'                   => ['required', 'digits:11', 'unique:candidatos,cpf'],
                'telefone'              => ['nullable', 'string', 'max:20'],
                'celular'               => ['required', 'string', 'max:20'],
                'data_nascimento'       => ['required', 'date'],
                'sexo'                  => ['required', 'string', 'max:1'],

                // Estado civil vindo do formulário (texto ou número)
                'estado_civil'          => ['nullable', 'string', 'max:50'],

                // Documento
                'doc_tipo'              => ['required', 'string', 'max:20'],
                'doc_numero'            => ['required', 'string', 'max:50'],
                'doc_orgao'             => ['required', 'string', 'max:50'],
                'doc_uf'                => ['required', 'string', 'max:2'],

                // Dados adicionais
                'escolaridade'          => ['required', 'string', 'max:100'],
                'nome_mae'              => ['required', 'string', 'max:160'],
                'nacionalidade'         => ['nullable', 'string', 'max:100'],
                'naturalidade_cidade'   => ['nullable', 'string', 'max:100'],
                'naturalidade_uf'       => ['nullable', 'string', 'max:2'],

                // Endereço / contato (nomes do FORM!)
                'endereco_cep'          => ['required', 'string', 'max:20'],
                'endereco_rua'          => ['required', 'string', 'max:160'],
                'endereco_numero'       => ['required', 'string', 'max:20'],
                'endereco_complemento'  => ['nullable', 'string', 'max:60'],
                'endereco_bairro'       => ['required', 'string', 'max:100'],
                'estado'                => ['required', 'string', 'max:2'],
                'cidade'                => ['required', 'string', 'max:100'],

                // Senha
                'password'              => [
                    'required',
                    'string',
                    'min:8',
                    'max:100',
                    'confirmed',
                ],
            ],
            [
                'cpf.digits'           => 'Informe um CPF válido com 11 dígitos.',
                'cpf.unique'           => 'Este CPF já está cadastrado.',
                'email.unique'         => 'Este e-mail já está cadastrado.',

                'celular.required'     => 'Informe um número de celular.',
                'data_nascimento.required' => 'Informe sua data de nascimento.',
                'sexo.required'        => 'Selecione o gênero.',

                'estado_civil.required'=> 'Informe o estado civil.',

                'doc_tipo.required'    => 'Informe o tipo de documento.',
                'doc_numero.required'  => 'Informe o número do documento.',
                'doc_orgao.required'   => 'Informe o órgão emissor.',
                'doc_uf.required'      => 'Informe a UF do documento.',

                'escolaridade.required'=> 'Informe sua escolaridade.',
                'nome_mae.required'    => 'Informe o nome da mãe.',

                'endereco_cep.required'=> 'Informe o CEP.',
                'endereco_rua.required'=> 'Informe o endereço.',
                'endereco_numero.required' => 'Informe o número.',
                'endereco_bairro.required' => 'Informe o bairro.',
                'estado.required'      => 'Informe o estado (UF).',
                'cidade.required'      => 'Informe a cidade.',

                'password.required'    => 'Informe uma senha.',
                'password.min'         => 'A senha deve conter pelo menos 8 caracteres.',
                'password.confirmed'   => 'A confirmação da senha não confere.',
            ]
        );

        $validated['cpf'] = $cpf;

        /**
         * Converte o estado civil do formulário para um código
         * numérico que o banco aceite (TINYINT, por exemplo).
         *
         * Aceita:
         *  - valor numérico direto: 1,2,3...
         *  - texto: "Solteiro(a)", "Casado(a)", etc.
         */
        $estadoCivilCode = null;
        if (!empty($validated['estado_civil'])) {
            $raw = trim($validated['estado_civil']);

            if (is_numeric($raw)) {
                $estadoCivilCode = (int) $raw;
            } else {
                $map = [
                    'Solteiro'       => 1,
                    'Solteiro(a)'    => 1,
                    'Casado'         => 2,
                    'Casado(a)'      => 2,
                    'Divorciado'     => 3,
                    'Divorciado(a)'  => 3,
                    'Viúvo'          => 4,
                    'Viúvo(a)'       => 4,
                    'Viuvo'          => 4,
                    'Viuvo(a)'       => 4,
                    'Separado'       => 5,
                    'Separado(a)'    => 5,
                    'União estável'  => 6,
                    'Uniao estavel'  => 6,
                    'Outro'          => 9,
                ];

                $estadoCivilCode = $map[$raw] ?? null;
            }
        }

        // Monta dados conforme seu modelo Candidato
        $data = [
            'nome'                 => $validated['nome'],
            'email'                => $validated['email'],
            'cpf'                  => $validated['cpf'],
            'telefone'             => $validated['telefone'] ?? null,
            'celular'              => $validated['celular'] ?? null,

            'data_nascimento'      => $validated['data_nascimento'],
            'sexo'                 => $validated['sexo'],

            // aqui vai o código numérico (ou null)
            'estado_civil'         => $estadoCivilCode,

            'doc_tipo'             => $validated['doc_tipo'],
            'doc_numero'           => $validated['doc_numero'],
            'doc_orgao'            => $validated['doc_orgao'],
            'doc_uf'               => strtoupper($validated['doc_uf']),

            'escolaridade'         => $validated['escolaridade'],

            'nome_mae'             => $validated['nome_mae'],
            'nacionalidade'        => $validated['nacionalidade'] ?? null,
            'naturalidade_cidade'  => $validated['naturalidade_cidade'] ?? null,
            'naturalidade_uf'      => isset($validated['naturalidade_uf'])
                                        ? strtoupper($validated['naturalidade_uf'])
                                        : null,

            // Nomes do form -> colunas do banco
            'endereco_cep'         => $validated['endereco_cep'],
            'endereco_rua'         => $validated['endereco_rua'],
            'endereco_numero'      => $validated['endereco_numero'],
            'endereco_complemento' => $validated['endereco_complemento'] ?? null,
            'endereco_bairro'      => $validated['endereco_bairro'],
            'estado'               => strtoupper($validated['estado']),
            'cidade'               => $validated['cidade'],

            'password'             => Hash::make($validated['password']),
            'status'               => true,

            // Muitos sistemas usam CPF como login
            'login'                => $validated['cpf'],
        ];

        /** @var \App\Models\Candidato $candidato */
        $candidato = Candidato::create($data);

        // Login automático no guard candidato
        Auth::guard('candidato')->login($candidato);

        // Opcional: envia e-mail de confirmação de cadastro,
        // se você criar a Notification App\Notifications\CandidatoCadastroRealizado
        if (class_exists(\App\Notifications\CandidatoCadastroRealizado::class)) {
            try {
                $candidato->notify(new \App\Notifications\CandidatoCadastroRealizado());
            } catch (\Throwable $e) {
                // silencia eventual erro de envio de e-mail
            }
        }

        // 👉 Depois de cadastrar, também vai direto para a home do candidato
        return redirect()
            ->route('candidato.home')
            ->with('status', 'Cadastro realizado com sucesso! Você já pode acessar a área do candidato.');
    }

    /* =======================================================
     * NOVO: Checagem de CPF (etapa 1 do login)
     * ======================================================= */

    /**
     * AJAX: verifica se CPF é válido e se já existe cadastro.
     *
     * - Se CPF inválido -> { ok:false, valid:false, exists:false, message:"..." }
     * - Se CPF válido e EXISTE -> { ok:true, valid:true, exists:true, name:"..." }
     * - Se CPF válido e NÃO EXISTE -> { ok:true, valid:true, exists:false, redirect:"url de cadastro" }
     */
    public function checkCpf(Request $request)
    {
        $cpfRaw = (string) $request->input('cpf');
        $cpf    = preg_replace('/\D+/', '', $cpfRaw);

        if ($cpf === '' || strlen($cpf) !== 11 || !$this->isValidCpf($cpf)) {
            return response()->json([
                'ok'      => false,
                'valid'   => false,
                'exists'  => false,
                'message' => 'Informe um CPF válido com 11 dígitos.',
            ]);
        }

        $user = Candidato::query()
            ->whereRaw("REPLACE(REPLACE(REPLACE(cpf,'.',''),'-',''),'/','') = ?", [$cpf])
            ->first();

        if ($user) {
            return response()->json([
                'ok'     => true,
                'valid'  => true,
                'exists' => true,
                'name'   => (string) $user->nome,
            ]);
        }

        return response()->json([
            'ok'       => true,
            'valid'    => true,
            'exists'   => false,
            'redirect' => route('candidato.register', ['cpf' => $cpf]),
        ]);
    }

    /**
     * Validação básica de CPF (regra dos dígitos verificadores).
     */
    private function isValidCpf(string $cpf): bool
    {
        // todos dígitos iguais invalida
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // cálculo do primeiro dígito
        $sum = 0;
        for ($i = 0, $j = 10; $i < 9; $i++, $j--) {
            $sum += (int) $cpf[$i] * $j;
        }
        $rest = ($sum * 10) % 11;
        if ($rest == 10) {
            $rest = 0;
        }
        if ($rest != (int) $cpf[9]) {
            return false;
        }

        // cálculo do segundo dígito
        $sum = 0;
        for ($i = 0, $j = 11; $i < 10; $i++, $j--) {
            $sum += (int) $cpf[$i] * $j;
        }
        $rest = ($sum * 10) % 11;
        if ($rest == 10) {
            $rest = 0;
        }

        return $rest == (int) $cpf[10];
    }
}
