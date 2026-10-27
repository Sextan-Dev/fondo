<?php

namespace App\Http\Controllers;

use App\Imports\Operations\IncomeImport;
use App\Imports\Operations\IncomeTemplate;
use App\OperationHistory;
use DataTables;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Validator;

/**
 * Income layouts: template, validation, booking, and the history of imports with their rollback.
 * A layout is uploaded once to check it; confirming books that same stored file.
 */
class IncomeImportController extends Controller
{
    // Uploaded but not yet confirmed, and the files of booked imports (storage/app)
    const PENDING = 'imports/pending';
    const STORED = 'imports/incomes';
    // How long a checked layout can wait for its confirmation
    const TOKEN_TTL = 86400;

    /*** TEMPLATE ***/
    public function template()
    {
        try {
            $path = tempnam(sys_get_temp_dir(), 'income-template-');
            $book = (new IncomeTemplate())->build();
            (new Xlsx($book))->save($path);
            $book->disconnectWorksheets();
            return response()->download($path, 'plantilla_ingresos.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend();
        } catch (\Exception $e) {
            sendNoticationError($e);
            return ['success' => false, 'msg' => _("Oups... Un error ha occurido : ") . $e->getMessage()];
        }
    }

    /*** CHECK: validate a layout without booking it ***/
    public function check(Request $request)
    {
        try {
            if ($message = $this->invalidUpload($request)) {
                return ['success' => false, 'msg' => $message];
            }
            $this->purgePending();

            $file = $request->file('incomes');
            $createDonors = $request->boolean('create_donors');
            $stored = $this->store($file);
            $result = (new IncomeImport())->validate(Storage::disk('local')->path($stored['path']), $createDonors, $stored['hash']);
            $canImport = $result['errors_total'] == 0;
            // nothing will confirm it: a corrected file comes in a new upload
            if (!$canImport) {
                Storage::disk('local')->delete($stored['path']);
            }

            return [
                'success' => true,
                'canImport' => $canImport,
                'token' => $canImport ? $this->token($stored + ['create_donors' => $createDonors]) : null,
                'result' => $this->report($result),
            ];
        } catch (\Exception $e) {
            sendNoticationError($e);
            return ['success' => false, 'msg' => _("Oups... Un error ha occurido : ") . $e->getMessage()];
        }
    }

    /*** CONFIRM: book a checked layout ***/
    public function confirm(Request $request)
    {
        try {
            $pending = $this->pending((string) $request->token);
            if (!$pending) {
                return ['success' => false, 'msg' => 'La validación expiró o no es válida: vuelve a subir el archivo.'];
            }
            @set_time_limit(600);

            return $this->exclusively(function () use ($pending) {
                // Checked again: donors or other imports may have changed since
                $disk = Storage::disk('local');
                if (!$disk->exists($pending['path'])) {
                    return ['success' => false, 'msg' => 'Este archivo ya se importó o la validación expiró: vuelve a subirlo.'];
                }
                $import = new IncomeImport();
                $result = $import->validate($disk->path($pending['path']), $pending['create_donors'], $pending['hash']);
                if ($result['errors_total'] > 0) {
                    return [
                        'success' => false,
                        'msg' => 'El archivo ya no pasa la validación: no se importó ningún ingreso. Revisa los errores.',
                        'result' => $this->report($result),
                    ];
                }

                $final = self::STORED . '/' . idCompany() . '/' . basename($pending['path']);
                $disk->copy($pending['path'], $final);
                try {
                    $history = $import->import($result, [
                        'original_name' => $pending['name'],
                        'path' => $final,
                        'hash' => $pending['hash'],
                    ]);
                } catch (\Throwable $e) {
                    $disk->delete($final);
                    throw $e;
                }
                $disk->delete($pending['path']);

                return [
                    'success' => true,
                    'msg' => 'Se importaron ' . number_format($history->rows_count) . ' ingresos (carga #' . $history->id . ').',
                    'history' => $history,
                ];
            });
        } catch (\Exception $e) {
            sendNoticationError($e);
            return ['success' => false, 'msg' => _("Oups... Un error ha occurido : ") . $e->getMessage()];
        }
    }

    /**
     * Upload from screens that still send the layout in one step: books it only when it has no errors,
     * creating the donors that don't exist, as that upload always did.
     */
    public function legacy(Request $request)
    {
        try {
            if ($message = $this->invalidUpload($request)) {
                return ['success' => false, 'msg' => $message];
            }
            @set_time_limit(600);

            $stored = $this->store($request->file('incomes'));

            return $this->exclusively(function () use ($stored) {
                $disk = Storage::disk('local');
                $import = new IncomeImport();
                $result = $import->validate($disk->path($stored['path']), true, $stored['hash']);
                if ($result['errors_total'] > 0) {
                    $disk->delete($stored['path']);
                    $errors = array_map(fn ($error) => ($error['row'] ? 'Fila ' . $error['row'] . ($error['field'] ? ', ' . $error['field'] : '') . ': ' : '') . $error['message'],
                        array_slice($result['errors'], 0, 5));
                    return ['success' => false, 'msg' => 'No se importó ningún ingreso (' . $result['errors_total'] . ' errores). ' . implode(' ', $errors)];
                }

                $final = self::STORED . '/' . idCompany() . '/' . basename($stored['path']);
                $disk->move($stored['path'], $final);
                try {
                    $history = $import->import($result, ['original_name' => $stored['name'], 'path' => $final, 'hash' => $stored['hash']]);
                } catch (\Throwable $e) {
                    $disk->delete($final);
                    throw $e;
                }

                return ['success' => true, 'msg' => 'Se importaron ' . number_format($history->rows_count) . ' ingresos (carga #' . $history->id . ').'];
            });
        } catch (\Exception $e) {
            sendNoticationError($e);
            return ['success' => false, 'msg' => _("Oups... Un Error ha ocurrido: ") . $e->getMessage()];
        }
    }

    /*** HISTORY ***/
    public function history(Request $request)
    {
        try {
            $history = DB::table('history_operations AS a')
                ->selectRaw('a.*, b.name, c.name as name_delete')
                ->leftJoin('users AS b', 'a.user_id', '=', 'b.id')
                ->leftJoin('users AS c', 'a.user_id_delete', '=', 'c.id')
                ->where(function ($query) {
                    // imports from before the company was recorded belong to their user's company
                    $query->where('a.company_id', idCompany())
                        ->orWhere(function ($query) {
                            $query->whereNull('a.company_id')->where('b.company_id', idCompany());
                        });
                })
                ->whereRaw('DATE(a.date) >= ? AND DATE(a.date) <= ?', [$request->from, $request->to]);
            if ($request->filled('status') && in_array($request->status, ['0', '1'], true)) {
                $history->where('a.status', $request->status);
            }
            if (!$request->has('order')) {
                $history->orderBy('a.id', 'desc');
            }

            return Datatables::of($history)->make(true);
        } catch (\Exception $e) {
            sendNoticationError($e);
            return ['success' => false, 'msg' => _("Oups... Un error ha occurido : ") . $e->getMessage()];
        }
    }

    public function show(Request $request)
    {
        try {
            $history = $this->findHistory($request->id);
            return [
                'success' => true,
                'history' => $history,
                'impact' => IncomeImport::impact($history),
                'hasFile' => $history->file_path && Storage::disk('local')->exists($history->file_path),
            ];
        } catch (\Exception $e) {
            sendNoticationError($e);
            return ['success' => false, 'msg' => _("Oups... Un error ha occurido : ") . $e->getMessage()];
        }
    }

    public function file(Request $request)
    {
        try {
            $history = $this->findHistory($request->id);
            if (!$history->file_path || !Storage::disk('local')->exists($history->file_path)) {
                return response()->json(['success' => false, 'msg' => 'El archivo de esta carga no se guardó.'], 404);
            }
            return Storage::disk('local')->download($history->file_path, $history->original_name ?: basename($history->file_path));
        } catch (\Exception $e) {
            sendNoticationError($e);
            return response()->json(['success' => false, 'msg' => _("Oups... Un error ha occurido : ") . $e->getMessage()], 500);
        }
    }

    /*** REVERT ***/
    public function revert(Request $request)
    {
        try {
            if (!actionDelete()) {
                return ['success' => false, 'msg' => _("Oups... No cuentas con los permisos necesarios para realizar esta accion. Por favor comunicate con el administrador del sistema.")];
            }
            $reason = trim((string) $request->reason);
            if (mb_strlen($reason) < 5 || mb_strlen($reason) > 500) {
                return ['success' => false, 'msg' => 'Escribe el motivo de la reversión (de 5 a 500 caracteres).'];
            }
            $history = $this->findHistory($request->id);
            if (!$history->type) {
                return ['success' => false, 'msg' => 'Esta carga no tiene sus registros marcados, por lo que no se puede revertir desde aquí.'];
            }
            if ((int) $history->status !== OperationHistory::ACTIVE) {
                return ['success' => false, 'msg' => 'Esta carga ya fue revertida.'];
            }

            $result = IncomeImport::revert($history, $reason);
            return [
                'success' => true,
                'msg' => 'Carga #' . $history->id . ' revertida: se eliminaron ' . number_format($result['operations']) . ' registros' .
                    ($result['donors'] == 1 ? ' y se desactivó 1 donador' : ($result['donors'] ? ' y se desactivaron ' . $result['donors'] . ' donadores' : '')) . '.',
            ];
        } catch (\Exception $e) {
            sendNoticationError($e);
            return ['success' => false, 'msg' => _("Oups... Un error ha occurido : ") . $e->getMessage()];
        }
    }

    /*** HELPERS ***/

    // One import at a time per company: two confirmations of the same file can't both pass the duplicate check
    private function exclusively(callable $callback)
    {
        $lock = 'fondo-income-import-' . idCompany();
        if (!DB::selectOne('SELECT GET_LOCK(?, 60) AS acquired', [$lock])->acquired) {
            return ['success' => false, 'msg' => 'Hay otra carga de ingresos en proceso: inténtalo de nuevo en un momento.'];
        }
        try {
            return $callback();
        } finally {
            DB::select('SELECT RELEASE_LOCK(?)', [$lock]);
        }
    }

    private function findHistory($id)
    {
        return OperationHistory::where('id', $id)
            ->where(function ($query) {
                $query->where('company_id', idCompany())
                    ->orWhere(function ($query) {
                        $query->whereNull('company_id')->whereIn('user_id', \App\User::select('id')->where('company_id', idCompany()));
                    });
            })
            ->firstOrFail();
    }

    private function invalidUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'incomes' => 'required|file|max:10240|extensions:xlsx',
        ], [
            'incomes.required' => 'Selecciona el archivo del layout.',
            'incomes.max' => 'El archivo no puede pasar de 10 MB.',
            'incomes.extensions' => 'El layout debe ser un archivo de Excel (.xlsx).',
        ]);
        return $validator->fails() ? $validator->errors()->first() : null;
    }

    private function store($file)
    {
        $name = date('Ymd_His') . '_' . Str::random(8) . '.xlsx';
        $path = Storage::disk('local')->putFileAs(self::PENDING, $file, $name);
        return [
            'path' => $path,
            'hash' => hash_file('sha256', $file->getRealPath()),
            'name' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 200),
        ];
    }

    // Layouts checked but never confirmed
    private function purgePending()
    {
        $disk = Storage::disk('local');
        foreach ($disk->files(self::PENDING) as $path) {
            if ($disk->lastModified($path) < time() - self::TOKEN_TTL) {
                $disk->delete($path);
            }
        }
    }

    private function token(array $pending)
    {
        return Crypt::encryptString(json_encode($pending + [
            'user' => authUser('id'),
            'company' => idCompany(),
            'at' => time(),
        ]));
    }

    private function pending($token)
    {
        try {
            $pending = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException $e) {
            return null;
        }
        $valid = is_array($pending)
            && $pending['user'] == authUser('id')
            && $pending['company'] == idCompany()
            && $pending['at'] > time() - self::TOKEN_TTL
            && Str::startsWith($pending['path'], self::PENDING . '/')
            && Storage::disk('local')->exists($pending['path']);
        return $valid ? $pending : null;
    }

    // The validation result without the parsed rows, which only the booking needs
    private function report(array $result)
    {
        unset($result['rows']);
        return $result;
    }
}
