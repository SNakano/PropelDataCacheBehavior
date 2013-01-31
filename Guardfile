guard 'phpunit', :tests_path => 'tests' do
  # Watch tests files
  watch(%r{^tests/.+Test\.php$})

  # Watch library files and run their tests
  watch(%r{^src/(.+)\.php$}) { |m| "tests/#{m[1]}Test.php" }
end