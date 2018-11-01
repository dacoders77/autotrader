<template>
    <div class="container">
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Signals table</h3>

                        <div class="card-tools">
                            <!-- Button trigger modal -->
                            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#newSignal">
                                <i class="fas fa-plus-square"></i> Update quotes
                            </button>
                            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#newSignal">
                                <i class="fas fa-plus-square"></i> Create user
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
                                <th>Open</th>
                                <th>Price</th>
                                <th>Close</th>
                                <th>Price</th>
                                <th>Action</th>
                                <th>Action</th>
                            </tr>
                            <tr>
                                <td>10/29/18 5:46 AM</td>
                                <td>BTCUSD</td>
                                <td>38</td>
                                <td>10</td>
                                <td class="green">Buy</td>
                                <td>7692.34</td>
                                <td>Filled</td>
                                <td>10/29/18 5:47 AM</td>
                                <td>7562.21</td>
                                <td>10/29/18 5:47 AM</td>
                                <td>7562.21</td>
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
                                <td class="red">Sell</td>
                                <td>7692.34 </td>
                                <td>Filled</td>
                                <td>10/29/18 5:47 AM</td>
                                <td>7562.21</td>
                                <td>10/29/18 5:47 AM</td>
                                <td>7562.21</td>
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


                <!-- /.card-header TEST TABLE DELEE-->
                <br><br>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover">
                        <tbody>
                        <tr>
                            <th>Id</th>
                            <th>Created</th>
                            <th>Name</th>
                            <th>Email</th>
                        </tr>
                        <tr v-for="user in users"  :key="user.id" >
                            <td>{{ user.id }}</td>
                            <td>{{ user.created_at | myDate}}</td> <!-- these functions stored in app.js -->
                            <td>{{ user.name | upText }}</td>
                            <td>{{ user.email }}</td>
                        </tr>
                        </tbody></table>
                </div>
                <!-- /.card-body -->


            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="newSignal" tabindex="-1" role="dialog" aria-labelledby="newSignalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newSignalLabel">Create new user</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="createUser">
                        <div class="modal-body">
                        <div class="form-group">
                            <input v-model="form.name" type="text" name="name"
                                   placeholder="Name"
                                   class="form-control" :class="{ 'is-invalid': form.errors.has('name') }">
                            <has-error :form="form" field="name"></has-error>
                        </div>
                        <div class="form-group">
                            <input v-model="form.email" type="email" name="email"
                                   placeholder="Email"
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
                            <button type="submit" class="btn btn-primary">Create user</button>
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
              users: {},
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
            loadUsers(){
                axios.get('api/user').then(({data}) => (this.users = data.data));
            },
            createUser(){
                this.$Progress.start(); // Progress bar
                this.form.post('api/user'); // Post request to the controller
                Fire.$emit('AfterCreate'); // Trigger an event of the global object which is declared in app.js

                $('#newSignal').modal('hide'); // Modal hide

                // Toast notification
                toast({
                    type: 'success',
                    title: 'User created successfully'
                });

                this.$Progress.finish();
            }
        },
        created() {
            this.loadUsers();
            //setInterval(() => this.loadUsers(), 3000); // Load users each 3 seconds
            Fire.$on('AfterCreate', () => {
                this.loadUsers();
            });

        }
    }
</script>


