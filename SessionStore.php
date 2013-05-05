<?PHP

namespace 'Firelit';

interface class SessionStore {
	
	public function set($name, $value, $expires = false);
	
	public function get($name);
	
	public function destroy();
	
}