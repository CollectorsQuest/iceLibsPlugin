require 'pathname'

# --------------------------------------------------
# Watchr Helpers
# --------------------------------------------------
def crawl(path, max_depth=nil, include_directories=false, depth=0, &block)
 return if max_depth && depth > max_depth
 begin
   if File.directory?(path)
     yield(path, depth) if include_directories
     files = Dir.entries(path).select{ |f| true unless f = ~/^\.{1,2}$/ }
     unless files.empty?
       files.collect!{ |file_path|
         crawl(path +'/'+ file_path, max_depth, include_directories, depth + 1, &block)
       }.flatten!
     end
     return files
   else
     yield(path, depth)
   end
 rescue SystemCallError => the_error
   warn "ERROR: #{the_error}"
 end
end

def lessc(input, output, web)
  print "[" + Time.now.strftime("%I:%M:%S") + "] compiling #{input.inspect.sub(web, '')}... "
  system "lessc #{input} #{output}"

  # Minify and Gzip if possible
  system "lessc --yui-compress #{input} #{output.gsub('.css', '.min.css')}"
  system "test -f #{output.gsub('.css', '.min.css')} && cat #{output.gsub('.css', '.min.css')} | gzip -9 -c > #{output}.gz"

  puts 'done'
end

def plessc(input, output, web)
  print "[" + Time.now.strftime("%I:%M:%S") + "] compiling #{input.inspect.sub(web, '')}... "
  system "php #{File.dirname(__FILE__)}/../data/bin/plessc -f=lessjs #{input} #{output}"

  # Minify and Gzip if possible
  system "php #{File.dirname(__FILE__)}/../data/bin/plessc -f=compressed #{input} #{output.gsub('.css', '.min.css')}"
  system "test -f #{output.gsub('.css', '.min.css')} && cat #{output} | gzip -9 -c > #{output}.gz"

  puts 'done'
end
