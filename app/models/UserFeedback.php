<?php

class UserFeedback
{
    private $id;
    private $user_id;
    private $feedback_type;
    private $subject;
    private $message;
    private $rating;
    private $status;
    private $priority;
    private $category;
    private $metadata;
    private $created_at;
    private $updated_at;

    public function __construct($data = [])
    {
        if (!empty($data)) {
            $this->id = $data['id'] ?? null;
            $this->user_id = $data['user_id'] ?? null;
            $this->feedback_type = $data['feedback_type'] ?? null;
            $this->subject = $data['subject'] ?? null;
            $this->message = $data['message'] ?? null;
            $this->rating = $data['rating'] ?? null;
            $this->status = $data['status'] ?? 'pending';
            $this->priority = $data['priority'] ?? 'medium';
            $this->category = $data['category'] ?? null;
            $this->metadata = $data['metadata'] ?? null;
            $this->created_at = $data['created_at'] ?? null;
            $this->updated_at = $data['updated_at'] ?? null;
        }
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->user_id; }
    public function getFeedbackType() { return $this->feedback_type; }
    public function getSubject() { return $this->subject; }
    public function getMessage() { return $this->message; }
    public function getRating() { return $this->rating; }
    public function getStatus() { return $this->status; }
    public function getPriority() { return $this->priority; }
    public function getCategory() { return $this->category; }
    public function getMetadata() { return $this->metadata; }
    public function getCreatedAt() { return $this->created_at; }
    public function getUpdatedAt() { return $this->updated_at; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setUserId($user_id) { $this->user_id = $user_id; }
    public function setFeedbackType($feedback_type) { $this->feedback_type = $feedback_type; }
    public function setSubject($subject) { $this->subject = $subject; }
    public function setMessage($message) { $this->message = $message; }
    public function setRating($rating) { $this->rating = $rating; }
    public function setStatus($status) { $this->status = $status; }
    public function setPriority($priority) { $this->priority = $priority; }
    public function setCategory($category) { $this->category = $category; }
    public function setMetadata($metadata) { $this->metadata = $metadata; }
    public function setCreatedAt($created_at) { $this->created_at = $created_at; }
    public function setUpdatedAt($updated_at) { $this->updated_at = $updated_at; }

    /**
     * Convert the feedback object to an array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'feedback_type' => $this->feedback_type,
            'subject' => $this->subject,
            'message' => $this->message,
            'rating' => $this->rating,
            'status' => $this->status,
            'priority' => $this->priority,
            'category' => $this->category,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}