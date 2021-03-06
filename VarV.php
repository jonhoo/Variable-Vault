<?php
  /**
   * Singleton-style registry for setting and getting variables globally
   * Full name: Variable Vault
   *
   * @author Jon Gjengset <jon@thesquareplanet.com>
   */
  class VarV {
    const DO_SET = 1;
    const DO_GET = 2;
    const DO_HAS = 3;
    const NOT_FOUND = 'This value was not found for $#!%&/()=? sake!';

    private static $_vars = array();
    private static $_locked = array();
    private static $_hasInstance = null;

    public function __construct() {

    }
    public function __destruct() {

    }

    /**
     * Sets a variable indexed by the given string ( $var ) to $val
     *
     * @param string $var The index of the value you wish to set. May be dot-separated to set subvalues
     * @param mixed $val The value to set
     * @param boolean $_locked Should the value be locked from further writing?
     * @return boolean False if the variable was locked, true otherwise
     */
    public static function set ( $var, $val, $locked = false ) {

      // Enables us to use __construct and __destruct
      if ( is_null ( self::$_hasInstance ) ) {
        self::$_hasInstance = new self();
      }

      $var = trim( $var, " \n\r\t." );
      if ( empty ( $var ) ) {
        trigger_error ( 'Cannot set using empty index!', E_USER_WARNING );
        return false;
      }

	  $lockedCount = count(self::$_locked);
      for ( $i = 0; $i < $lockedCount; $i++ ) {
		if ( strncmp ( $var . '.', self::$_locked[$i] . '.', strlen ( $var . '.' ) ) === 0 ) {
          trigger_error( 'Cannot override locked variable ' . $var . '!', E_USER_WARNING );
          return false;
        }
      }

      if ( is_array( $val ) ) {
        $success = true;
        foreach ( $val as $key => $value ) {
          $success = self::set( $var . '.' . $key, $value ) && $success;
        }
        return $success;
      }

      if ( $locked ) {
        self::$_locked[] = $var;
      }


      $node = &self::findVarArrayIndexFromDotString( $var, self::DO_SET );
      $node[array_pop( explode ( '.', $var ) )] = $val;
      return true;
    }

    /**
     * Removes the given variable ( and all subvariables )
     * Possible names: axe, bye, bat, cut, ban, eat, hit, tux, off, pop, rob, saw, zap
     *
     * @param string $var Variable to remove
     * @return boolean Success?
     */
    public static function zap ( $var ) {

      $var = trim( $var, " \n\r\t." );
      if ( empty ( $var ) ) {
        trigger_error ( 'Cannot unset empty index!', E_USER_WARNING );
        return false;
      }

	  $lockedCount = count(self::$_locked);
      for ( $i = 0; $i < $lockedCount; $i++ ) {
		if ( strncmp ( $var . '.', self::$_locked[$i] . '.', strlen ( $var . '.' ) ) === 0 ) {
          trigger_error( 'Cannot override locked variable ' . $var . '!', E_USER_WARNING );
          return false;
        }
      }

      $node = &self::findVarArrayIndexFromDotString( $var, self::DO_SET );
      unset($node[array_pop( explode ( '.', $var ) )]);
      return true;
    }

    /**
     * Checks if the given variable has been set
     *
     * @param string $var Variable to set
     * @return boolean Exists?
     */
    public static function has ( $var ) {
      return self::findVarArrayIndexFromDotString( $var, self::DO_HAS ) !== self::NOT_FOUND;
    }

    /**
     * Returns the value indexed by the given variable
     *
     * @param string $var Index ( dot-separated for namespaces and subvariables )
     * @return mixed Element at index on success, false on failure
     */
    public static function get( $var ) {
      return self::getByRef($var);
    }

    /**
     * Set a value by reference to the given index
     * WARNING: Any subsequent sets/setByRefs run on subindexes of this element
     * will also overridethe values in the original referenced array!
     * Therefore, this function will completely overwrite the given index,
     * not merge like set does, when given an array as value.
     * Because of this, this method also locks the key by default, and
     * $locked = false must be given to allow subsequent sets
     *
     *
     * @param mixed $var
     * @param mixed $val
     * @param boolean $locked
     * @return true on success, false on failure
     */
    public static function setByRef ( $var, &$val, $locked = true ) {
      // Enables us to use __construct and __destruct
      if ( is_null ( self::$_hasInstance ) ) {
        self::$_hasInstance = new self();
      }

      $var = trim( $var, " \n\r\t." );
      if ( empty ( $var ) ) {
        trigger_error ( 'Cannot set using empty index!', E_USER_WARNING );
        return false;
      }

	  $lockedCount = count(self::$_locked);
      for ( $i = 0; $i < $lockedCount; $i++ ) {
		if ( strncmp ( $var . '.', self::$_locked[$i] . '.', strlen ( $var . '.' ) ) === 0 ) {
          trigger_error( 'Cannot override locked variable ' . $var . '!', E_USER_WARNING );
          return false;
        }
      }

      if ( $locked ) {
        self::$_locked[] = $var;
      }

      $node = &self::findVarArrayIndexFromDotString( $var, self::DO_SET );
      $node[array_pop( explode ( '.', $var ) )] = $val;
      return true;
    }

    /**
     * Returns the value indexed by the given variable by reference
     *
     *
     * @param string $var Index ( dot-separated for namespaces and subvariables )
     * @return mixed Element at index on success, false on failure
     */
    public static function &getByRef($var) {
      $node = &self::findVarArrayIndexFromDotString( $var, self::DO_GET );
      if ( $node !== self::NOT_FOUND ) {
        return $node;
      } else {
        // trigger_error( 'Variable ' . $var . ' is not set!', E_USER_WARNING );
        return false;
      }
    }

    /**
     * Finds the node with the given index
     *
     * @param string $index The index
     * @param DO_SET|DO_GET|DO_HAS $action What we're doing ( for error handling )
     */
    private static function &findVarArrayIndexFromDotString($index, $action) {
      $idxs = explode('.', $index);
      $atVar = &self::$_vars;
	  $idx_count = count($idxs);
	  $idx_countMinusOne = $idx_count - 1;
      for ($i = 0; $i < $idx_count; $i++) {
        $idx = $idxs[$i];

        if ($action === self::DO_SET && !is_array($atVar[$idx]) && $i < $idx_countMinusOne && array_key_exists($idx, $atVar) && ) {
          $atVar[$idx] = array();
        }

        if ($i < $idx_countMinusOne && (!is_array($atVar) || !array_key_exists($idx, $atVar))) {
          if ($action === self::DO_GET || $action === self::DO_HAS) {
            if ($action === self::DO_GET) {
              // trigger_error ( 'Variable ' . $idx . ' was not found in namespace ' . implode('.', array_slice( $idxs, 0, $i ) ), E_USER_NOTICE );
            }
            return self::NOT_FOUND;
          } else {
            $atVar[$idx] = array();
          }
        }

        if ($i == $idx_countMinusOne) {
          if ($action == self::DO_GET || $action == self::DO_HAS) {
            if (is_array($atVar) && array_key_exists($idx, $atVar)) {
              return $atVar[$idx];
            } else {
              if ($action == self::DO_GET) {
                // trigger_error ( 'Variable ' . $idx . ' was not found in namespace ' . implode('.', array_slice( $idxs, 0, $i ) ), E_USER_NOTICE );
              }
              return self::NOT_FOUND;
            }
          } else {
            return $atVar;
          }
        }
        $atVar = &$atVar[$idx];
      }

      return $atVar[array_pop($idxs)];
    }

    /**
     * Returns all values in Vault
     *
     * @param boolean $return Determines if the variables should be returned or printed
     * @return Depends on the value of $return
     */
    public static function poo ($return = false, $size = 11) {
      if ($return === false) {
          print_r(self::$_vars);
      } else {
        return self::$_vars;
      }
    }
  }
?>
