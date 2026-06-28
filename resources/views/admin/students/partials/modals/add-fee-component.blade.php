            {{-- Add Fee Component Modal --}}
            <div class="modal fade" id="addFeeComponentModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content" style="border-radius: 20px;">
                        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 20px 20px 0 0;">
                            <h5 class="modal-title font-weight-bold">
                                <i class="fas fa-plus-circle mr-2"></i>
                                Add Fee Component to {{ $student->name }}
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div id="loadingFeeComponents" class="text-center py-4" style="display: none;">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                                <p class="text-muted">Loading available fee components...</p>
                            </div>

                            <div id="feeComponentsList">
                                {{-- Components will be loaded here via AJAX --}}
                            </div>

                            <div id="noFeeComponents" class="text-center py-4" style="display: none;">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h6 class="text-muted">All Fee Components Assigned</h6>
                                <p class="text-muted">This student has been assigned all available fee components.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                            </div>
                        </div>
                    </div>
                </div>


            </div>
