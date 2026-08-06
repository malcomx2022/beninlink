<div class="modal fade" id="todoeditModal{{$todo->id}}"  role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content" id="data-modal">
        <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">{{ __('to_do.to_do_edit')}}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        </div>
        <form action="{{ route('todo.update')}}" method="post">
            @csrf
            @method('put')
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <input type="hidden" name="id" value="{{$todo->id}}">
                        <div class="form-group">
                            <label for="transfer_hub">{{ __('to_do.title')}}</label> <span class="text-danger">*</span>
                            <div class="form-control-wrap  h">
                                <input id="" type="text" placeholder="{{ __('placeholder.Enter_title') }}" name="title" value="{{$todo->title}}" class="form-control">
                                @error('title')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="user">{{ __('to_do.assign')}}</label> <span class="text-danger">*</span>
                            <select id="user" name="user_id" class="form-control">
                                <option selected disabled>{{ __('menus.select') }}  {{ __('user.title') }}</option>
                                @foreach (user() as $user)
                                    <option {{$todo->user_id == $user->id? 'selected':''}} value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('user')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group  ">
                            <label for="note">{{ __('to_do.date')}}</label> <span class="text-danger">*</span>
                            <div class="form-control-wrap user-search">
                                <input type="date" name="date" value="{{$todo->date}}" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="note">{{ __('to_do.description')}}</label>
                            <div class="form-control-wrap user-search">
                                <textarea class="form-control" name="description" rows="5" id="description" value="">{{$todo->description}}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('levels.cancel') }}</button>
                <button type="submit" id="transfer_to_hub_multiple_parcel_button" class="btn btn-primary">{{ __('levels.save_change') }}</button>
            </div>
        </form>

    </div>
</div>
</div>
