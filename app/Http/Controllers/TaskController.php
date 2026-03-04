<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    /**
     * Listar tarefas do usuário logado com paginação.
     */
    public function index(Request $request): Response
    {
        $query = $request->user()->tasks()->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            match ($request->input('status')) {
                'completed' => $query->completed(),
                'pending' => $query->pending(),
                'overdue' => $query->overdue(),
                default => null,
            };
        }

        $tasks = $query->paginate(10)->withQueryString();

        return Inertia::render('tasks/Index', [
            'tasks' => $tasks,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Formulário de criação de tarefa.
     */
    public function create(): Response
    {
        return Inertia::render('tasks/Create');
    }

    /**
     * Salvar nova tarefa.
     */
    public function store(StoreTaskRequest $request)
    {
        $request->user()->tasks()->create($request->validated());

        return redirect()->route('tasks.index')
            ->with('success', 'Tarefa criada com sucesso.');
    }

    /**
     * Formulário de edição de tarefa.
     */
    public function edit(Task $task): Response
    {
        $this->authorizeTask($task);

        return Inertia::render('tasks/Edit', [
            'task' => $task,
        ]);
    }

    /**
     * Atualizar tarefa existente.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task->update($request->validated());

        return redirect()->route('tasks.index')
            ->with('success', 'Tarefa atualizada com sucesso.');
    }

    /**
     * Excluir tarefa.
     */
    public function destroy(Task $task)
    {
        $this->authorizeTask($task);

        $task->delete();

        return redirect()->route('tasks.index')
            ->with('success', 'Tarefa excluída com sucesso.');
    }

    /**
     * Marcar tarefa como concluída ou pendente (toggle).
     */
    public function toggleComplete(Task $task)
    {
        $this->authorizeTask($task);

        if ($task->completed) {
            $task->markAsPending();
        } else {
            $task->markAsCompleted();
        }

        return redirect()->back()
            ->with('success', $task->completed ? 'Tarefa concluída.' : 'Tarefa reaberta.');
    }

    /**
     * Garante que a tarefa pertence ao usuário logado.
     */
    private function authorizeTask(Task $task): void
    {
        if ($task->user_id !== auth()->id()) {
            abort(403, 'Acesso não autorizado.');
        }
    }
}
