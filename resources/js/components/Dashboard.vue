<template>
    <div class="container">

        <!--<signals></signals>-->

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

                            <button type="button" class="btn btn-success" @click="newModal">
                                <i class="fas fa-plus-square"></i> Create user
                            </button>
                        </div>
                    </div>

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
                            <th>Action</th>
                        </tr>
                        <tr v-for="user in users"  :key="user.id" >
                            <td>{{ user.id }}</td>
                            <td>{{ user.created_at | myDate}}</td> <!-- these functions stored in app.js -->
                            <td>{{ user.name | upText }}</td>
                            <td>{{ user.email }}</td>
                            <td>
                                <a href="#" @click="deleteUser(user.id)">Delete</a>/
                                <a href="#" @click="editModal(user)">Edit</a>
                            </td>
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
                        <h5 class="modal-title" v-show="!editmode" id="newSignalLabel">Create new user</h5>
                        <h5 class="modal-title" v-show="editmode" id="newSignalLabel">Update user</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="editmode ? updateUser() : createUser()">
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
                            <button v-show="editmode" type="submit" class="btn btn-success">Update user</button>
                            <button v-show="!editmode" type="submit" class="btn btn-primary">Create user</button>
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
              editmode: false, // Variable
              users: {}, // Object
              form: new Form({ // Class instance
                  id: '',
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
            editModal(user){
                this.editmode = true;
                this.form.reset(); // Reset form function. https://github.com/cretueusebiu/vform
                $('#newSignal').modal('show');
                this.form.fill(user);
                console.log(user);
            },
            newModal(){
                this.editmode = false;
                this.form.reset();
                $('#newSignal').modal('show');
            },
            updateUser(){
                //console.log('function called');
                this.$Progress.start();
                this.form.put('api/user/' + this.form.id)
                    .then(() => {
                        $('#newSignal').modal('hide');
                        swal(
                            'Updated!',
                            'User has been updated',
                            'success'
                        )
                        this.$Progress.finish();
                        Fire.$emit('AfterCreate');
                    })
                    .catch(() => {
                        this.$Progress.fail();
                    });
            },
            deleteUser(id){
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
                        this.form.delete('api/user/' + id).then(() => {
                            if (result.value) {
                                swal(
                                    'Deleted!',
                                    'User has been deleted.',
                                    'success'
                                )
                                Fire.$emit('AfterCreate');
                            }
                        }).catch(() => {
                            swal("Failed!", "Something bad happened..", "warning");
                        })
                    }
                })
            },
            loadUsers(){
                axios.get('api/user').then(({data}) => (this.users = data.data));
            },
            createUser(){
                // Progress bar
                this.$Progress.start();
                // Post request to the controller
                this.form.post('api/user')
                .then(() => {
                    // Request successfull
                    Fire.$emit('AfterCreate'); // Trigger an event of the global object which is declared in app.js
                    $('#newSignal').modal('hide'); // Modal hide
                    // Toast notification
                    toast({
                        type: 'success',
                        title: 'User created successfully'
                    });
                    this.$Progress.finish();

                })
                .catch(() => {
                // Error

                })
            }
        },
        created() {
            this.loadUsers();
            //setInterval(() => this.loadUsers(), 3000); // Load users each 3 seconds

            // Event listener
            Fire.$on('AfterCreate', () => {
                this.loadUsers();
            });

        }
    }
</script>


