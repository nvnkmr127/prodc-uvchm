{{-- Activity Timeline Styles --}}
<style>
.activity-timeline {
    position: relative;
}

.activity-timeline::before {
    content: '';
    position: absolute;
    left: 30px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #007bff, #6f42c1, #17a2b8);
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
    padding-left: 70px;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    z-index: 1;
}

.timeline-content {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    border-left: 4px solid #007bff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.timeline-content:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.timeline-header h6 {
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.timeline-meta {
    flex-shrink: 0;
    min-width: 100px;
}

.toggle-details {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}

.toggle-details:focus {
    box-shadow: none;
}

.toggle-details[aria-expanded="true"] i {
    transform: rotate(180deg);
}

.timeline-details .card {
    border: none;
    font-size: 0.85rem;
}

.properties-list small {
    line-height: 1.6;
}

/* Filter animations */
.timeline-item {
    transition: all 0.3s ease;
}

.timeline-item.filtered-out {
    opacity: 0;
    transform: scale(0.95);
    margin-bottom: 0;
    height: 0;
    overflow: hidden;
    padding: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .activity-timeline::before {
        left: 20px;
    }
    
    .timeline-item {
        padding-left: 50px;
    }
    
    .timeline-marker {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .timeline-content {
        padding: 1rem;
    }
    
    .timeline-meta {
        min-width: auto;
        margin-top: 0.5rem;
    }
    
    .d-flex.justify-content-between {
        flex-direction: column !important;
    }
}
</style>
