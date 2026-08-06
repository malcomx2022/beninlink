import 'package:dropdown_button2/dropdown_button2.dart';
import 'package:flutter/material.dart';

// ignore: must_be_immutable
class DropDownWidget extends StatefulWidget {
  DropDownWidget({
    super.key,
    required this.itemList,
    this.selectedValue,
    required this.initialValue,
    required this.onChanged,
    this.images,
  });

  final List<String> itemList;
  void Function(String?)? onChanged;
  String? selectedValue;
  final String? initialValue;
  final String? images;

  @override
  State<DropDownWidget> createState() => _DropDownWidgetState();
}

class _DropDownWidgetState extends State<DropDownWidget> {
  @override
  Widget build(BuildContext context) {
    return DropdownButtonFormField2<String>(
      isExpanded: true,
      decoration: InputDecoration(
        focusColor: Colors.red,
        hoverColor: Colors.red,
        // Add Horizontal padding using menuItemStyleData.padding so it matches
        // the menu padding when button's width is not specified.
        contentPadding: const EdgeInsets.symmetric(vertical: 16),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(5),
          // borderSide: BorderSide.none,
        ),
        filled: true,
        fillColor: Colors.white,
        // Add more decoration..
      ),
      hint: Text(
        widget.initialValue!,
        style: const TextStyle(fontSize: 14),
      ),
      items: widget.itemList
          .map((item) => DropdownMenuItem<String>(
                value: item,
                child: Text(
                  item,
                  style: const TextStyle(
                    fontSize: 14,
                  ),
                ),
              ))
          .toList(),
      validator: (value) {
        if (value == null) {
          return 'Please select value.';
        }
        return null;
      },
      value: widget.selectedValue,
      onChanged: widget.onChanged,
      onSaved: (String? value) {
        widget.selectedValue = value;
      },
      buttonStyleData: const ButtonStyleData(
        padding: EdgeInsets.only(right: 8),
      ),
      iconStyleData: const IconStyleData(
        icon: Padding(
            padding: EdgeInsets.only(right: 8.0),
            child: Icon(
              Icons.keyboard_arrow_down_outlined,
              size: 28,
            )),
      ),
      dropdownStyleData: DropdownStyleData(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(15),
        ),
      ),
      menuItemStyleData: const MenuItemStyleData(
        padding: EdgeInsets.symmetric(horizontal: 16),
      ),
    );
  }
}
