<div class="modal fade" id="bulkEditModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit text-warning mr-2"></i>Bulk Edit Attendance
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="bulkEditForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        You are editing <span id="bulkEditCount">0</span> attendance records.
                    </div>
                    
                    <input type="hidden" id="bulkEditIds" name="attendance_ids">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    
                    <div class="form-group">
                        <label for="bulkStatus">Status <span class="text-danger">*</span></label>
                        <select name="status" id="bulkStatus" class="form-control" required>
                            <option value="">Select Status</option>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                            <option value="excused">Excused</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="bulkRemarks">Remarks (Optional)</label>
                        <textarea name="remarks" id="bulkRemarks" class="form-control" rows="3" 
                                placeholder="Add remarks for all selected records..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Update Records
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>