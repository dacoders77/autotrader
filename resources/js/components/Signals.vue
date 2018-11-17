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
                                    <th>Action</th>
                                    <th>Edit</th>
                                    <th>Created</th>
                                    <th>Symbol</th>
                                    <th>Multiplier</th>
                                    <th>Status</th>
                                    <th>%</th>

                                    <th>Lvrg</th>
                                    <th>Side</th>
                                    <th>Quote</th>


                                    <th>Open</th>
                                    <th>Price</th>
                                    <th>Close</th>
                                    <th>Price</th>



                                </tr>
                                <tr v-for="signal in signals.data" :key="signal.id" :class="signal.status == 'finished' ? 'grey' : '' ">
                                    <td>{{ signal.id }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <div v-if="signal.status == 'new'">
                                                <button class="btn btn-success" @click="executeSymbol(signal)"><i class="fas fa-play"></i></button>
                                            </div>
                                            <div v-if="signal.status == 'proceeded'">
                                                <button class="btn btn-danger" @click="executeSymbol(signal)"><i class="fas fa-stop"></i></button>
                                            </div>
                                            <div v-if="signal.status == 'error' || signal.status == 'finished'">
                                                <button class="btn btn-light" disabled><i class="fas fa-check"></i></button>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-primary" @click="deleteSignal(signal.id)"><i class="nav-icon fas fa-trash white"></i></button>
                                            <button v-if="signal.status == 'new'" class="btn btn-secondary" @click="editModal(signal)"><i class="nav-icon fas fa-edit white"></i></button>
                                            <button v-if="signal.status == 'error' || signal.status == 'proceeded' || signal.status == 'finished'" class="btn btn-secondary" disabled @click="editModal(signal) "><i class="nav-icon fas fa-edit white"></i></button>
                                        </div>
                                    </td>


                                    <td>{{ signal.created_at | myDate }}</td>
                                    <td>{{ signal.symbol }}</td>
                                    <td>{{ signal.multiplier }} </td>
                                    <td>{{ signal.status }}</td>
                                    <td>{{ signal.percent }}</td>

                                    <td>{{ signal.leverage }}</td>
                                    <td>{{ signal.direction }}</td>
                                    <td>{{ signal.quote }}</td>


                                    <td>{{ signal.open_date }}</td>
                                    <td>{{ signal.open_price }}</td>
                                    <td>{{ signal.close_date }}</td>
                                    <td>{{ signal.close_price }}</td>


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
                            <!--
                            <div class="form-group">
                                <input v-model="form.symbol" type="text" name="symbol"
                                       placeholder="Symbol"
                                       class="form-control" :class="{ 'is-invalid': form.errors.has('symbol') }">
                                <has-error :form="form" field="symbol"></has-error>
                            </div>

                            <div class="form-group">
                                <input v-model="form.multiplier" type="number" step="0.00000001" name="multiplier"
                                       placeholder="Multiplier (ETH: 0.000001)"
                                       class="form-control" :class="{ 'is-invalid': form.errors.has('multiplier') }">
                                <has-error :form="form" field="multiplier"></has-error>
                            </div>
                            -->

                            <div class="form-group">
                                <select name="symbol" v-model="form.symbol" id="symbol" class="form-control" :class="{ 'is-invalid': form.errors.has('symbol') }">
                                    <option value="">Symbol</option>

                                    <option v-for="symbol in symbols.data">
                                        {{ symbol.execution_name }}
                                    </option>

                                </select>
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
                            <button v-show="editmode" type="submit" class="btn btn-success">Update signal</button>
                            <button v-show="!editmode" type="submit" class="btn btn-primary">Create signal</button>
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
                symbols: {}, // Symbols for drop down menu in create/update modal
                form: new Form({ // Class instance
                    id: '',
                    symbol: '',
                    multiplier: '',
                    percent: '',
                    leverage: '',
                    direction: '',
                })
            }
        },
        methods:{
            // Symbol test execution. DELEE
            executeSymbol(signal){

                swal({
                    title: 'Are you sure?',
                    text: "Signal will be proceeded!!",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, proceed it!'
                }).then((result) => {
                    // Ajax request
                    if (result.value){
                        axios.post('exec', signal)
                            .then(response => {
                                swal(
                                    'Proceeded!',
                                    'Signal has been proceeded',
                                    'success'
                                )
                                Fire.$emit('AfterCreateSignal');
                            })
                            .catch(error => {
                                //swal("Failed!", "Error: \n" + error.response.data, "warning");
                                swal("Failed!", "Error: \n" + error.response.data.message, "warning");

                                //console.log(error.response.data.message);
                                /*
                                for(var i in error){
                                    console.log(i, error[i]);
                                }
                                */
                                Fire.$emit('AfterCreateSignal');
                            });
                    }
                })


            },
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
                axios.get('api/symbol').then(({data}) => (this.symbols = data));
            },
            createSignal(){
                // Progress bar
                this.$Progress.start();
                // Post request to the controller
                this.form.post('api/signal')
                    .then(() => {
                        // Request successful
                        Fire.$emit('AfterCreateSignal'); // Trigger an event of the global object which is declared in app.js
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
                        Fire.$emit('AfterCreateSignal');
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
                                Fire.$emit('AfterCreateSignal');
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
            Fire.$on('AfterCreateSignal', () => {
                this.loadUsers();
            });
        }
    }
</script>
