<div class="modal fade" id="todoStatus{{ \App\Enums\TodoStatus::PROCESSING }}"  role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content" id="data-modal">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">{{ __('to_do.'.\App\Enums\TodoStatus::PROCESSING) }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('todo.processing') }}" method="post">
            @csrf
            <input type="hidden" id="todo_id" value="" name="todo_id" class="modal_todo_id"/>
            <div class="modal-body">
                <div class="form-group  ">
                    <label for="note">{{ __('to_do.note')}}</label>
                    <div class="form-control-wrap deliveryman-search">
                       <textarea class="form-control" name="note" rows="5" id="note"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('levels.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('levels.submit') }}</button>
            </div>
        </form>
    </div>
  </div>
</div>
