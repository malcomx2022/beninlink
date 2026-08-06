<div role="tabpanel" class="tab-pane wow zoomIn" id="database-configuration-tab">
    <h3 class="title">Database Configuration</h3>
    <div class="section clearfix">
        <p>Please enter your database connection details.</p>
        <hr />
        <div class="row">
            <div class="col-md-6">
            <div class="form-group mt-2 ">
                <label for="host" >Database Host</label>
                    <input type="text" value="{{old('host') ?? 'localhost'}}" id="host"  name="host" class="form-control  form--control" placeholder="Database Host (usually localhost)"  />
                </div>
            </div>
            <div class=" col-md-6">
            <div class="form-group  mt-2">
                <label for="dbuser" >Database User</label>
                    <input type="text" value="{{old('dbuser') ?? ''}}" name="dbuser" class="form-control  form--control" autocomplete="off" placeholder="Database user name" />
                </div>
            </div>
            <div class=" col-md-6">
            <div class="form-group  mt-2">
                <label for="dbpassword" >Password</label>
                    <input type="password" value="{{old('dbpassword') ?? ''}}" name="dbpassword" class="form-control  form--control" autocomplete="off" placeholder="Database user password" />
                </div>
            </div>
            <div class=" col-md-6">
            <div class="form-group  mt-2">
                <label for="dbname" >Database Name</label>
                    <input type="text" value="{{old('dbname') ?? ''}}" name="dbname" class="form-control  form--control" placeholder="Database Name" />
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between mt-5">
        <button   class="btn btn-primary previous"  type="button"><i class='fa fa-arrow-left'></i> Preview</button>
        <button   class="btn btn-primary form-next" type="button">  Next  <i class='fa fa-arrow-right'></i></button>
    </div>
</div>
