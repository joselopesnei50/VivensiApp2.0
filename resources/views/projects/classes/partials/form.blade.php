{{-- Formulario compartilhado create/edit --}}
<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    @if($errors->any())
        <div class="alert alert-danger rounded-3">
            <strong>Corrija os erros abaixo:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body p-4">
            <div class="mb-3">
                <label class="form-label fw-600">Nome da turma <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control form-control-lg"
                       value="{{ old('name', $model->name ?? '') }}"
                       placeholder="Ex: Aula de Violão Iniciante Segunda 19h" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-600">Descrição</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $model->description ?? '') }}</textarea>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-600">Professor</label>
                    <select name="default_teacher_user_id" class="form-select">
                        <option value="">— sem professor definido —</option>
                        @foreach($teachers as $id => $name)
                            <option value="{{ $id }}" {{ old('default_teacher_user_id', $model->default_teacher_user_id ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-600">Modo de chamada padrão</label>
                    <select name="default_mode" class="form-select">
                        <option value="fechada" {{ old('default_mode', $model->default_mode ?? 'fechada') === 'fechada' ? 'selected' : '' }}>Fechada (só matriculados)</option>
                        <option value="aberta" {{ old('default_mode', $model->default_mode ?? '') === 'aberta' ? 'selected' : '' }}>Aberta (autocadastro)</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-600">Horário início</label>
                    <input type="time" name="default_start_time" class="form-control"
                           value="{{ old('default_start_time', $model->default_start_time ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-600">Horário fim</label>
                    <input type="time" name="default_end_time" class="form-control"
                           value="{{ old('default_end_time', $model->default_end_time ?? '') }}">
                </div>

                <div class="col-12">
                    <label class="form-label fw-600 d-block mb-2">Dias da semana</label>
                    @php
                        $selectedDays = old('weekdays', $model?->weekdays ?? []);
                        $days = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom'];
                    @endphp
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach($days as $num => $label)
                            <div class="form-check">
                                <input type="checkbox" name="weekdays[]" value="{{ $num }}" id="day{{ $num }}"
                                       class="form-check-input"
                                       {{ in_array($num, $selectedDays) ? 'checked' : '' }}>
                                <label class="form-check-label" for="day{{ $num }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-600">Início vigência</label>
                    <input type="date" name="start_date" class="form-control"
                           value="{{ old('start_date', $model?->start_date?->format('Y-m-d') ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-600">Fim vigência</label>
                    <input type="date" name="end_date" class="form-control"
                           value="{{ old('end_date', $model?->end_date?->format('Y-m-d') ?? '') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-600">Vagas máximas</label>
                    <input type="number" name="max_students" class="form-control" min="1" max="1000"
                           value="{{ old('max_students', $model->max_students ?? '') }}"
                           placeholder="Sem limite">
                </div>

                @if($model)
                    <div class="col-md-6">
                        <label class="form-label fw-600">Status</label>
                        <select name="status" class="form-select">
                            <option value="ativo" {{ old('status', $model->status ?? '') === 'ativo' ? 'selected' : '' }}>Ativo</option>
                            <option value="encerrado" {{ old('status', $model->status ?? '') === 'encerrado' ? 'selected' : '' }}>Encerrado</option>
                        </select>
                    </div>
                @endif

                <div class="col-12">
                    <label class="form-label fw-600">Notas internas</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes', $model->notes ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">{{ $submitText }}</button>
</form>
