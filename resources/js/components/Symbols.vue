<template>
    <div class="container" >
        <div class="row mt-3">
            <div class="col"><!--<div style="width: 100%">-->
                <div class="card h-100">
                    <div class="card-header"><span style="font-size:140%">Symbols</span>
                        <div class="card-tools">
                            <!-- Button trigger modal -->
                        </div>
                        <button type="button" class="btn btn-success float-right" @click="newModal">
                            <i class="fas fa-plus-square"></i> Symbol
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
                                    <th>Action</th>
                                    <th>Execution name</th>
                                    <th>Leverage name</th>
                                    <th>Quote</th>
                                    <th>Formula</th>

                                </tr>
                                <tr v-for="symbol in symbols.data" :key="symbol.id">
                                    <td>{{ symbol.id }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-primary" @click="deleteSymbol(symbol.id)">
                                                <i class="nav-icon fas fa-trash white"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>{{ symbol.created_at | myDate }}</td>
                                    <td>{{ symbol.execution_name }}</td>
                                    <td>{{ symbol.leverage_name }}</td>
                                    <td>{{ symbol.quote_value }}</td>
                                    <td>{{ symbol.formula }}</td>


                                </tr>
                                </tbody></table>
                        </div>
                        <!-- /.card-body -->
                        <!-- Pagination -->
                        <div class="card-footer">

                            <ul class="pagination justify-content-center">
                                <pagination :data="symbols" @pagination-change-page="getResults"></pagination>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100">
                    <div class="card-header"><span style="font-size:140%">Quotes</span>
                        <div class="card-tools">

                        </div>
                    </div>
                    <div class="card-body">
                        <span v-for="limitOrderStatus in limitOrderStatuses">
                          <small>
                              {{ limitOrderStatus }}<br>
                          </small>
                        </span>
                    </div>
                </div>
            </div>

        </div>


        <!-- Modal -->
        <div class="modal fade" id="addNewSignalModal" tabindex="-1" role="dialog" aria-labelledby="newSignalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" v-show="!editmode" id="newSignalLabel">Add new symbol</h5>
                        <h5 class="modal-title" v-show="editmode" id="newSignalLabel">Update client</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form @submit.prevent="editmode ? updateClient() : createClient()">
                        <div class="modal-body">
                            <div class="form-group">
                                <input v-model="form.execution_name" type="text" name="execution_name"
                                       placeholder="Execution symbol name"
                                       class="form-control" :class="{ 'is-invalid': form.errors.has('execution_name') }">
                                <has-error :form="form" field="execution_name"></has-error>
                            </div>
                            <div class="form-group">
                                <input v-model="form.leverage_name" type="text" name="leverage_name"
                                       placeholder="Leverage symbol name"
                                       class="form-control" :class="{ 'is-invalid': form.errors.has('leverage_name') }">
                                <has-error :form="form" field="leverage_name"></has-error>
                            </div>
                            <div class="form-group">
                                <select name="type" v-model="form.formula" id="type2" class="form-control" :class="{ 'is-invalid': form.errors.has('formula') }">
                                    <option value="">Formula</option>
                                    <option value="=1/symbolQuote(BTC)">=1/symbolQuote(BTC)</option>
                                    <option value="=symbolQuote*multp(ETH)">=symbolQuote*multp(ETH)</option>
                                    <option value="=symbolQuote">=symbolQuote</option>
                                </select>
                                <has-error :form="form" field="formula"></has-error>
                            </div>
<!--                            <div class="form-group">
                                <input v-model="form.min_exec_quantity" type="number" name="min_exec_quantity"
                                       placeholder="Minimum execution quantity"
                                       class="form-control" :class="{ 'is-invalid': form.errors.has('min_exec_quantity') }">
                                <has-error :form="form" field="min_exec_quantity"></has-error>
                            </div>-->
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
                            <button v-show="!editmode" type="submit" class="btn btn-primary">Create symbol</button>
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
                symbols: {},
                limitOrderStatuses: [],
                form: new Form({ // Class instance
                    execution_name: '',
                    leverage_name: '',
                    formula: '',
                    info: ''
                })
            }
        },
        methods:{
            // Pagination. https://github.com/gilbitron/laravel-vue-pagination
            getResults(page = 1) {
                axios.get('api/client?page=' + page)
                    .then(response => {
                        this.symbols = response.data;
                    });
            },
            newModal(){
                this.editmode = false;
                this.form.reset();
                $('#addNewSignalModal').modal('show');
            },
            loadSymbols(){
                axios.get('api/symbol').then(({data}) => (this.symbols = data)); // Resource controllers are defined in api.php
            },
            createClient(){
                // Progress bar
                this.$Progress.start();
                // Post request to the controller
                this.form.post('api/symbol')
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
                    .catch(() => {
                        // Error
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
            deleteSymbol(id){
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
                        this.form.delete('api/symbol/' + id).then(() => {
                            if (result.value) {
                                swal(
                                    'Deleted!',
                                    'Symbol has been deleted.',
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




            this.loadSymbols();
            // Event listener
            Fire.$on('AfterCreate', () => {
                this.loadSymbols();
            });

            // Websocket listener
            // Sent from WebSocketStream.php
            Echo.channel('ATTR')
                .listen('AttrUpdateEvent', (e) => {
                    this.symbols = e.update.symbol;
                    //console.log(e.update.symbol.data[0]);

                    // Quotes
                    if (this.limitOrderStatuses.length < 11) { // 11 - quantity of rows in Order trace window
                        this.limitOrderStatuses.push(e.update.ticker + ' ' + e.update.price);


                    }
                    else {
                        this.limitOrderStatuses.shift();
                        this.limitOrderStatuses.push(e.update.ticker + ' ' + e.update.price);

                    }

                });

        }
    }
</script>
