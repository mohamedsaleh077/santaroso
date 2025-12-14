<?php

namespace Objects;

class Validation
{
    private $errors = array();

    /*
     * invalid data checks
     * @param array $values
     * ex: $values = array('username' => 'whatever', 'password' => 'whatever also', .... );
     * @return void
     */
    public function emptyCheck(array $values)
    {
        foreach ($values as $key => $value) {
            if (empty($value)) {
                $this->errors["empty_" . $key] = "Please fill out all required fields.";
            }
        }
    }

    public function max255(array $values)
    {
        foreach ($values as $key => $value) {
            if (mb_strlen($value) > 255) {
                $this->errors["max255_" . $key] = "Please keep your message under 255 characters.";
            }
        }
    }

    public function max5000(array $values)
    {
        foreach ($values as $key => $value) {
            if (mb_strlen($value) > 5000) {
                $this->errors["max255_" . $key] = "Please keep your message under 5000 characters.";
            }
        }
    }

    public function setError(string $key, string $error)
    {
        $this->errors[$key] = $error;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function CSRF($user_token, $csrf_token){
        if ($user_token !== $csrf_token){
            $this->errors["csrf"] = "An Error Happen, Please try again after refreshing the page.";
        }
    }
}