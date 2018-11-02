<template>
    <div class="container" >
        <div class="row mt-3">
            <div style="width: 100%">
                <div class="card">
                    <div class="card-header"><span style="font-size:140%">Signals table</span>
                        <div class="card-tools">
                            <!-- Button trigger modal -->
                        </div>
                        <button type="button" class="btn btn-success float-right" @click="newModal">
                            <i class="fas fa-plus-square"></i> Signal
                        </button>

                    </div>

                    <div class="card-body">
                        <!-- /.card-header -->
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover">
                                <tbody>
                                <tr>
                                    <th><i class="fas fa-info-circle blue"></i></th>
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
                                <tr v-for="signal in signals.data" :key="signal.id">
                                    <td>{{ signal.id }}</td>
                                    <td>{{ signal.created_at | myDate }}</td>
                                    <td>{{ signal.symbol }}</td>
                                    <td>{{ signal.percent }}</td>

                                    <td>{{ signal.leverage }}</td>
                                    <td>{{ signal.direction }}</td>
                                    <td>{{ signal.quote }}</td>
                                    <td>{{ signal.status }}</td>

                                    <td>{{ signal.open_date }}</td>
                                    <td>{{ signal.open_price }}</td>
                                    <td>{{ signal.close_date }}</td>
                                    <td>{{ signal.close_price }}</td>

                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-success">Open</button>
                                            <button class="btn btn-danger">Close</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">

                                            <button class="btn btn-success" @click="deleteSignal(signal.id)">
                                                <i class="nav-icon fas fa-trash white"></i>
                                            </button>
                                            <button class="btn btn-danger" @click="editModal(signal)">
                                                <i class="nav-icon fas fa-edit white"></i>
                                            </button>

                                            <!--
                                            <button class="btn btn-success"><i class="nav-icon fas fa-edit white"></i></button>
                                            <button class="btn btn-danger"><i class="nav-icon fas fa-trash white"></i></button>-->
                                        </div>
                                    </td>
                                </tr>
                                </tbody></table>
                        </div>
                        <!-- /.card-body -->
                        <!-- Pagination -->
                        <div class="card-footer">

                            <ul class="pagination justify-content-center">
                                <pagination :data="signals" @pagination-change-page="getResults"></pagination>
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
                        <h5 class="modal-title" v-show="!editmode" id="newSignalLabel">Add new signal</h5>
                        <h5 class="modal-title" v-show="editmode" id="newSignalLabel">Update signal</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form @submit.prevent="editmode ? updateSignal() : createSignal()">
                        <div class="modal-body">
                            <div class="form-group">
                                <input v-model="form.symbol" type="text" name="symbol"
                                       placeholder="Symbol"
                                       class="form-control" :class="{ 'is-invalid': form.errors.has('symbol') }">
                                <has-error :form="form" field="symbol"></has-error>
                            </div>
                            <div class="form-group">
                                <input v-model="form.percent" type="number" name="percent"
                                       placeholder="%"
                                       class="form-control" :class="{ 'is-invalid': form.errors.has('percent') }">
                                <has-error :form="form" field="percent"></has-error>
                            </div>
                            <div class="form-group">
                                <input v-model="form.leverage" type="number" name="leverage"
                                       placeholder="Leverage"
                                       class="form-control" :class="{ 'is-invalid': form.errors.has('leverage') }">
                                <has-error :form="form" field="leverage"></has-error>
                            </div>
                            <div class="form-group">
                                <select name="type" v-model="form.direction" id="type" class="form-control" :class="{ 'is-invalid': form.errors.has('direction') }">
                                    <option value="">Side(direction)</option>
                                    <option value="long">Long</option>
                                    <option value="short">Short</option>
                                </select>
                                <has-error :form="form" field="direction"></has-error>
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
                editmode: false, // Modal edit record or create new flag
                signals: {},
                form: new Form({ // Class instance
                    id: '',
                    symbol: '',
                    percent: '',
                    leverage: '',
                    direction: '',
                })
            }
        },
        methods:{
            // Pagination. https://github.com/gilbitron/laravel-vue-pagination
            getResults(page = 1) {
                axios.get('api/signal?page=' + page)
                    .then(response => {
                        this.signals = response.data;
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
            loadUsers(){
                axios.get('api/signal').then(({data}) => (this.signals = data)); // Resource controllers are defined in api.php
                //console.log(this.users);
            },
            createSignal(){
                // Progress bar
                this.$Progress.start();
                // Post request to the controller
                this.form.post('api/signal')
                    .then(() => {
                        // Request successful
                        Fire.$emit('AfterCreate'); // Trigger an event of the global object which is declared in app.js
                        $('#addNewSignalModal').modal('hide'); // Modal hide
                        // Toast notification
                        toast({
                            type: 'success',
                            title: 'Signal created successfully'
                        });
                        this.$Progress.finish();
                    })
                    .catch(() => {
                        // Error
                    })
            },
            updateSignal(){
                this.$Progress.start();
                this.form.put('api/signal/' + this.form.id)
                    .then(() => {
                        $('#addNewSignalModal').modal('hide');
                        swal(
                            'Updated!',
                            'Signal has been updated',
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
                        this.form.delete('api/signal/' + id).then(() => {
                            if (result.value) {
                                swal(
                                    'Deleted!',
                                    'Signal has been deleted.',
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
            this.loadUsers();

            // Event listener
            Fire.$on('AfterCreate', () => {
                this.loadUsers();
            });
        }
    }
</script>
