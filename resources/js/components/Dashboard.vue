<template>
    <div class="container">
        <div class="row mt-3">
            <div class="col-md-24">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Signals table</h3>

                        <div class="card-tools">
                            <!-- Button trigger modal -->
                            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#newSignal">
                                New signal
                            </button>

                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover">
                            <tbody>
                            <tr>
                                <th>Created</th>
                                <th>Symbol</th>
                                <th>%</th>
                                <th>Lvrg</th>
                                <th>Side</th>
                                <th>Quote</th>
                                <th>Status</th>
                                <th>Opn date</th>
                                <th>Opn price</th>
                                <th>Cls date</th>
                                <th>Cls price</th>
                                <th>Action</th>
                                <th>Action</th>
                            </tr>
                            <tr>
                                <td>10/29/18 5:46 AM</td>
                                <td>BTCUSD</td>
                                <td>38</td>
                                <td>10</td>
                                <td>Buy</td>
                                <td>7692.3412</td>
                                <td>Filled</td>
                                <td>10/29/18 5:47 AM</td>
                                <td>7562.2111</td>
                                <td>10/29/18 5:47 AM</td>
                                <td>7562.2111</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-success">Open</button>
                                        <button class="btn btn-danger">Close</button>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-success"><i class="nav-icon fas fa-edit white"></i></button>
                                        <button class="btn btn-danger"><i class="nav-icon fas fa-trash white"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>10/29/18 5:46 AM</td>
                                <td>BTCUSD</td>
                                <td>38</td>
                                <td>10</td>
                                <td>Buy</td>
                                <td>7692.3412</td>
                                <td>Filled</td>
                                <td>10/29/18 5:47 AM</td>
                                <td>7562.2111</td>
                                <td>10/29/18 5:47 AM</td>
                                <td>7562.2111</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-success">Open</button>
                                        <button class="btn btn-danger">Close</button>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-success"><i class="nav-icon fas fa-edit white"></i></button>
                                        <button class="btn btn-danger"><i class="nav-icon fas fa-trash white"></i></button>
                                    </div>
                                </td>
                            </tr>

                            </tbody></table>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="newSignal" tabindex="-1" role="dialog" aria-labelledby="newSignalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newSignalLabel">Add new signal</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="createUser">
                        <div class="modal-body">
                        <div class="form-group">
                            <input v-model="form.name" type="text" name="name"
                                   placeholder="Symbol"
                                   class="form-control" :class="{ 'is-invalid': form.errors.has('name') }">
                            <has-error :form="form" field="name"></has-error>
                        </div>
                        <div class="form-group">
                            <input v-model="form.email" type="email" name="email"
                                   placeholder="%"
                                   class="form-control" :class="{ 'is-invalid': form.errors.has('email') }">
                            <has-error :form="form" field="email"></has-error>
                        </div>
                        <div class="form-group">
                            <select name="type" v-model="form.type" id="type" class="form-control" :class="{ 'is-invalid': form.errors.has('type') }">
                                <option value="">Side(direction)</option>
                                <option value="admin">Long</option>
                                <option value="user">Short</option>
                            </select>
                            <has-error :form="form" field="type"></has-error>
                        </div>
                    </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


    </div>
</template>

<script>
    export default {
        data(){
          return{
              form: new Form({
                  name: '',
                  email: '',
                  password: '',
                  type: '',
                  bio: '',
                  photo: ''
              })

          }
        },
        methods:{
            createUser(){
                this.form.post('api/user');
            }
        },
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>


