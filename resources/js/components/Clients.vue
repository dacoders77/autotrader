<template>
    <div class="container" >
        <div class="row mt-3">
            <div style="width: 100%">
                <div class="card">
                    <div class="card-header"><span style="font-size:140%">Clients & APIs</span>
                        <div class="card-tools">
                            <!-- Button trigger modal -->
                        </div>
                        <button type="button" class="btn btn-success float-right" @click="newModal">
                            <i class="fas fa-plus-square"></i> Client
                        </button>

                    </div>

                    <div class="card-body">
                        <!-- /.card-header -->
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover">
                                <tbody>
                                <tr>
                                    <th><i class="fas fa-info-circle blue"></i></th>
                                    <th>Active</th>
                                    <th>Validate</th>
                                    <th>Valid</th>
                                    <th>Action</th>
                                    <th>Created</th>

                                    <th>Name</th>
                                    <th>LastName</th>
                                    <th>Telegram</th>

                                    <th>Email</th>
                                    <th>Api</th>
                                    <th>Secret</th>
                                    <th>Status</th>

                                    <th>Funds</th>
                                    <th>Info</th>


                                </tr>
                                <tr v-for="signal in clients.data" :key="signal.id">
                                    <td>{{ signal.id }}</td>
                                    <td>


                                        <div v-if="signal.active == '1'">
                                            <button class="btn btn-default" @click="activateClient(signal)">
                                                <i class="fas fa-check-square"></i></button>
                                        </div>

                                        <div v-if="signal.active == '0'">
                                            <button class="btn btn-default" @click="activateClient(signal)">
                                                <i class="far fa-square"></i></button>
                                        </div>


                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-warning" @click="validateClient(signal)">
                                            <i class="nav-icon fas fa-redo white"></i></button>
                                        </div>
                                    </td>
                                    <td>True</td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-primary" @click="deleteSignal(signal.id)">
                                                <i class="nav-icon fas fa-trash white"></i>
                                            </button>
                                            <button class="btn btn-danger" @click="editModal(signal)">
                                                <i class="nav-icon fas fa-edit white"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>{{ signal.created_at | myDate }}</td>
                                    <td>{{ signal.name }}</td>
                                    <td>{{ signal.last_name }}</td>

                                    <td>{{ signal.telegram }}</td>
                                    <td>{{ signal.email }}</td>
                                    <td>{{ signal.api }}</td>
                                    <td>{{ signal.api_secret }}</td>

                                    <td>{{ signal.status }}</td>
                                    <td>{{ signal.funds }}</td>
                                    <td>{{ signal.info }}</td>
                                </tr>
                                </tbody></table>
                        </div>
                        <!-- /.card-body -->
                        <!-- Pagination -->
                        <div class="card-footer">

                            <ul class="pagination justify-content-center">
                                <pagination :data="clients" @pagination-change-page="getResults"></pagination>
                            </ul>


                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modal -->
        <div class="modal fade" id="addNewSignalModal" tabindex="-1" role="dialog" aria-labelledby="newSignalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" v-show="!editmode" id="newSignalLabel">Add new client</h5>
                        <h5 class="modal-title" v-show="editmode" id="newSignalLabel">Update client</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form @submit.prevent="editmode ? updateClient() : createClient()">
                        <div class="modal-body">
                            <div class="form-group">
                                <input v-model="form.name" type="text" name="name"
                                       placeholder="Name"
                                       class="form-control" :class="{ 'is-invalid': form.errors.has('name') }">
                                <has-error :form="form" field="name"></has-error>
                            </div>
                            <div class="form-group">
                                <input v-model="form.last_name" type="text" name="last_name"
                                       placeholder="Last Name"
                                       class="form-control" :class="{ 'is-invalid': form.errors.has('last_name') }">
                                <has-error :form="form" field="last_name"></has-error>
                            </div>
                            <div class="form-group">
                                <input v-model="form.telegram" type="text" name="telegram"
                                       placeholder="Telegram"
                                       class="form-control" :class="{ 'is-invalid': form.errors.has('telegram') }">
                                <has-error :form="form" field="telegram"></has-error>
                            </div>
                            <div class="form-group">
                                <input v-model="form.email" type="text" name="email"
                                       placeholder="Email"
                                       class="form-control" :class="{ 'is-invalid': form.errors.has('email') }">
                                <has-error :form="form" field="email"></has-error>
                            </div>
                            <div class="form-group">
                                <input v-model="form.api" type="text" name="api"
                                       placeholder="Api"
                                       class="form-control" :class="{ 'is-invalid': form.errors.has('api') }">
                                <has-error :form="form" field="api"></has-error>
                            </div>
                            <div class="form-group">
                                <input v-model="form.api_secret" type="text" name="api_secret"
                                       placeholder="Api secret"
                                       class="form-control" :class="{ 'is-invalid': form.errors.has('api_secret') }">
                                <has-error :form="form" field="api_secret"></has-error>
                            </div>
                            <div class="form-group">
                                <input v-model="form.info" type="text" name="info"
                                       placeholder="Info"
                                       class="form-control" :class="{ 'is-invalid': form.errors.has('info') }">
                                <has-error :form="form" field="info"></has-error>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button v-show="editmode" type="submit" class="btn btn-success">Update client</button>
                            <button v-show="!editmode" type="submit" class="btn btn-primary">Create client</button>
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
                editmode: false, // Modal edit record or create new flag
                clients: {},
                form: new Form({ // Class instance
                    id: '',
                    name: '',
                    last_name: '',
                    telegram: '',
                    email: '',
                    api: '',
                    api_secret: '',
                    info: ''
                })
            }
        },
        methods:{
            activateClient(client){
                axios.post('activateclient', client)
                    .then(response => {
                        Fire.$emit('AfterCreate');
                    })
                    .catch(error => {
                        swal("Failed!", "Error: \n" + error.response.data.message, "warning");
                        Fire.$emit('AfterCreate');
                    });
                },
            validateClient(client){
            axios.post('validateclient', client)
                .then(response => {
                    swal(
                        'Proceeded!',
                        response.data.message, // Response from ClientController.php
                        'success'
                    )
                    console.log(response);
                    Fire.$emit('AfterCreateSignal');
                })
                .catch(error => {
                    swal("Failed!", "Error: \n" + error.response.data.message, "warning");

                    //console.log(error.response.data.message);
                    /*
                    for(var i in error){
                        console.log(i, error[i]);
                    }
                    */
                    Fire.$emit('AfterCreateSignal');
                });



            },
            // Pagination. https://github.com/gilbitron/laravel-vue-pagination
            getResults(page = 1) {
                axios.get('api/client?page=' + page)
                    .then(response => {
                        this.clients = response.data;
                    });
            },
            editModal(signal){
                this.editmode = true;
                this.form.reset(); // Reset form function. https://github.com/cretueusebiu/vform
                $('#addNewSignalModal').modal('show');
                this.form.fill(signal);
                console.log(signal);
            },
            newModal(){
                this.editmode = false;
                this.form.reset();
                $('#addNewSignalModal').modal('show');
            },
            loadClients(){
                axios.get('api/client').then(({data}) => (this.clients = data)); // Resource controllers are defined in api.php
                //console.log(this.users);
            },
            createClient(){
                // Progress bar
                this.$Progress.start();
                // Post request to the controller
                this.form.post('api/client')
                    .then(() => {
                        // Request successful
                        Fire.$emit('AfterCreate'); // Trigger an event of the global object which is declared in app.js
                        $('#addNewSignalModal').modal('hide'); // Modal hide
                        // Toast notification
                        toast({
                            type: 'success',
                            title: 'Client created successfully'
                        });
                        this.$Progress.finish();
                    })
                    .catch((error) => {
                        // Error
                        //alert(error.response.data.message);
                        //console.log(error.response.data);
                    })
            },
            updateClient(){
                this.$Progress.start();
                this.form.put('api/client/' + this.form.id)
                    .then(() => {
                        $('#addNewSignalModal').modal('hide');
                        swal(
                            'Updated!',
                            'Client has been updated',
                            'success'
                        )
                        this.$Progress.finish();
                        Fire.$emit('AfterCreate');
                    })
                    .catch(() => {
                        this.$Progress.fail();
                    });
            },
            deleteSignal(id){
                swal({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    // Ajax request
                    // Delete request type
                    if (result.value){
                        this.form.delete('api/client/' + id).then(() => {
                            if (result.value) {
                                swal(
                                    'Deleted!',
                                    'Client has been deleted.',
                                    'success'
                                )
                                Fire.$emit('AfterCreate');
                            }
                        }).catch(() => {
                            swal("Failed!", "Something bad happened..", "warning");
                        })
                    }
                })
            }
        },
        created() {
            this.loadClients();

            // Event listener
            Fire.$on('AfterCreate', () => {
                this.loadClients();
            });
        }
    }
</script>

